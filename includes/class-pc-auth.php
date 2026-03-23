<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Auth
 *
 * Responsabilidades:
 * - Validar ID tokens de Google contra la API de Google.
 * - Crear y destruir sesiones de acceso (transient + cookie).
 * - Verificar que el email autenticado coincida con el del contrato.
 *
 * No usa librerías externas — solo wp_remote_post y transients de WordPress.
 *
 * Flujo:
 * 1. Frontend obtiene google_id_token via Google Identity Services.
 * 2. Lo envía al endpoint AJAX pc_google_auth.
 * 3. Backend valida el token con Google.
 * 4. Compara email vs client_email del contrato.
 * 5. Si coincide: crea sesión, devuelve éxito.
 * 6. Frontend recarga — ahora muestra contract-view.
 */
class PC_Auth {

    const COOKIE_NAME      = 'pc_session';
    const SESSION_TTL      = 28800; // 8 horas
    const TRANSIENT_PREFIX = 'pc_sess_';

    // ----------------------------------------------------------------
    // Validación del token de Google
    // ----------------------------------------------------------------

    /**
     * Valida un Google ID token contra la API de Google.
     *
     * @param  string $id_token
     * @return array|WP_Error  { sub, email, name, picture } o WP_Error.
     */
    public static function validate_google_token( $id_token ) {
        if ( empty( $id_token ) ) {
            return new WP_Error( 'empty_token', __( 'Token vacío.', 'pylaris-contracts' ) );
        }

        $client_id = self::get_google_client_id();

        if ( empty( $client_id ) ) {
            return new WP_Error( 'no_client_id', __( 'Google Client ID no configurado. Contactá al administrador.', 'pylaris-contracts' ) );
        }

        $response = wp_remote_post(
            'https://oauth2.googleapis.com/tokeninfo',
            array(
                'body'    => array( 'id_token' => $id_token ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            return new WP_Error(
                'google_api_error',
                __( 'No se pudo conectar con Google. Intentá nuevamente.', 'pylaris-contracts' )
            );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $code || empty( $body ) ) {
            return new WP_Error( 'invalid_token', __( 'Token de Google inválido.', 'pylaris-contracts' ) );
        }

        // Verificar audience: el token debe haber sido emitido para nuestro Client ID
        $token_aud = $body['aud'] ?? '';
        if ( $token_aud !== $client_id ) {
            return new WP_Error( 'token_audience_mismatch', __( 'Token no válido para esta aplicación.', 'pylaris-contracts' ) );
        }

        // Verificar expiración
        if ( ! empty( $body['exp'] ) && (int) $body['exp'] < time() ) {
            return new WP_Error( 'token_expired', __( 'La sesión de Google expiró. Iniciá sesión nuevamente.', 'pylaris-contracts' ) );
        }

        $email = isset( $body['email'] ) ? strtolower( trim( $body['email'] ) ) : '';

        if ( empty( $email ) ) {
            return new WP_Error( 'no_email', __( 'No se pudo obtener el email de Google.', 'pylaris-contracts' ) );
        }

        return array(
            'sub'     => sanitize_text_field( $body['sub'] ?? '' ),
            'email'   => $email,
            'name'    => sanitize_text_field( $body['name'] ?? '' ),
            'picture' => esc_url_raw( $body['picture'] ?? '' ),
        );
    }

    // ----------------------------------------------------------------
    // Gestión de sesión
    // ----------------------------------------------------------------

    /**
     * Crea una sesión para el acceso a un contrato específico.
     * Guarda datos en transient de WP y setea una cookie httpOnly.
     *
     * @param  int    $contract_id
     * @param  string $contract_token
     * @param  array  $google_data
     * @return string Session key generada.
     */
    public static function create_session( $contract_id, $contract_token, array $google_data ) {
        $session_key = bin2hex( random_bytes( 24 ) );

        $session_data = array(
            'contract_id'    => (int) $contract_id,
            'contract_token' => $contract_token,
            'google_sub'     => $google_data['sub'],
            'google_email'   => $google_data['email'],
            'google_name'    => $google_data['name'],
            'created_at'     => time(),
            'expires_at'     => time() + self::SESSION_TTL,
        );

        set_transient(
            self::TRANSIENT_PREFIX . $session_key,
            $session_data,
            self::SESSION_TTL
        );

        $cookie_value = $session_key . '|' . $contract_token;

        setcookie(
            self::COOKIE_NAME,
            $cookie_value,
            array(
                'expires'  => time() + self::SESSION_TTL,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );

        $_COOKIE[ self::COOKIE_NAME ] = $cookie_value;

        return $session_key;
    }

    /**
     * Obtiene la sesión activa para un contrato específico.
     * Lee cookie → busca transient → valida integridad.
     *
     * @param  string $contract_token
     * @return array|null
     */
    public static function get_session( $contract_token ) {
        $cookie = $_COOKIE[ self::COOKIE_NAME ] ?? '';

        if ( empty( $cookie ) ) {
            return null;
        }

        $parts = explode( '|', $cookie, 2 );
        if ( count( $parts ) !== 2 ) {
            return null;
        }

        list( $session_key, $cookie_token ) = $parts;

        // Cookie debe pertenecer a este contrato
        if ( ! hash_equals( $contract_token, $cookie_token ) ) {
            return null;
        }

        $session_key = preg_replace( '/[^a-f0-9]/', '', $session_key );
        if ( empty( $session_key ) ) {
            return null;
        }

        $session_data = get_transient( self::TRANSIENT_PREFIX . $session_key );

        if ( false === $session_data ) {
            return null;
        }

        if ( time() > ( $session_data['expires_at'] ?? 0 ) ) {
            delete_transient( self::TRANSIENT_PREFIX . $session_key );
            return null;
        }

        if ( ! hash_equals( $contract_token, $session_data['contract_token'] ?? '' ) ) {
            return null;
        }

        return $session_data;
    }

    /**
     * Destruye la sesión activa: borra transient y cookie.
     */
    public static function destroy_session() {
        $cookie = $_COOKIE[ self::COOKIE_NAME ] ?? '';

        if ( ! empty( $cookie ) ) {
            $parts       = explode( '|', $cookie, 2 );
            $session_key = preg_replace( '/[^a-f0-9]/', '', $parts[0] ?? '' );

            if ( $session_key ) {
                delete_transient( self::TRANSIENT_PREFIX . $session_key );
            }
        }

        setcookie(
            self::COOKIE_NAME,
            '',
            array(
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax',
            )
        );

        unset( $_COOKIE[ self::COOKIE_NAME ] );
    }

    // ----------------------------------------------------------------
    // Verificación de acceso
    // ----------------------------------------------------------------

    /**
     * Verifica que el email de la sesión coincide con el email del contrato.
     *
     * @param  object $contract
     * @param  array  $session
     * @return bool
     */
    public static function session_matches_contract( $contract, array $session ) {
        $session_email  = strtolower( trim( $session['google_email'] ?? '' ) );
        $contract_email = strtolower( trim( $contract->client_email ?? '' ) );

        if ( empty( $session_email ) || empty( $contract_email ) ) {
            return false;
        }

        return hash_equals( $contract_email, $session_email );
    }

    // ----------------------------------------------------------------
    // AJAX endpoint
    // ----------------------------------------------------------------

    /**
     * Registra los hooks AJAX del sistema de autenticación.
     * Se llama desde PC_Loader.
     */
    public static function register_ajax_hooks() {
        add_action( 'wp_ajax_nopriv_pc_google_auth', array( __CLASS__, 'handle_google_auth' ) );
        add_action( 'wp_ajax_pc_google_auth',        array( __CLASS__, 'handle_google_auth' ) );
        add_action( 'wp_ajax_nopriv_pc_logout',      array( __CLASS__, 'handle_logout' ) );
        add_action( 'wp_ajax_pc_logout',             array( __CLASS__, 'handle_logout' ) );
    }

    /**
     * Endpoint AJAX: recibe el ID token de Google y el token del contrato.
     * Espera POST: { nonce, id_token, contract_token }
     */
    public static function handle_google_auth() {
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );

        if ( ! wp_verify_nonce( $nonce, 'pc_google_auth' ) ) {
            wp_send_json_error( array( 'message' => __( 'Token de seguridad inválido.', 'pylaris-contracts' ) ), 403 );
        }

        $id_token       = sanitize_text_field( $_POST['id_token'] ?? '' );
        $contract_token = preg_replace( '/[^a-zA-Z0-9]/', '', $_POST['contract_token'] ?? '' );

        if ( empty( $id_token ) || empty( $contract_token ) ) {
            wp_send_json_error( array( 'message' => __( 'Datos incompletos.', 'pylaris-contracts' ) ), 400 );
        }

        // Buscar contrato
        $contract = PC_DB::get_contract_by_token( $contract_token );

        if ( ! $contract || $contract->status !== 'pending' ) {
            wp_send_json_error( array( 'message' => __( 'Contrato no disponible.', 'pylaris-contracts' ) ), 404 );
        }

        // Validar token de Google
        $google_data = self::validate_google_token( $id_token );

        if ( is_wp_error( $google_data ) ) {
            PC_DB::log_event( $contract->id, 'google_login_failed', array(
                'reason' => $google_data->get_error_code(),
                'ip'     => PC_Helpers::get_client_ip(),
            ) );

            wp_send_json_error( array( 'message' => $google_data->get_error_message() ), 401 );
        }

        // Verificar que el email autenticado coincide con el del contrato
        $session_email  = strtolower( trim( $google_data['email'] ) );
        $contract_email = strtolower( trim( $contract->client_email ) );

        if ( ! hash_equals( $contract_email, $session_email ) ) {
            PC_DB::log_event( $contract->id, 'google_login_failed', array(
                'reason'          => 'email_mismatch',
                'attempted_email' => $session_email,
                'ip'              => PC_Helpers::get_client_ip(),
            ) );

            wp_send_json_error( array(
                'message'       => __( 'Este contrato fue asignado a otra cuenta de Google.', 'pylaris-contracts' ),
                'access_denied' => true,
                'google_email'  => $session_email,
            ), 403 );
        }

        // Todo OK — crear sesión
        self::create_session( $contract->id, $contract_token, $google_data );

        PC_DB::log_event( $contract->id, 'google_login_success', array(
            'google_email' => $session_email,
            'google_sub'   => $google_data['sub'],
            'ip'           => PC_Helpers::get_client_ip(),
        ) );

        wp_send_json_success( array(
            'message'  => __( 'Acceso verificado.', 'pylaris-contracts' ),
            'redirect' => home_url( '/c/' . $contract_token ),
        ) );
    }

    /**
     * Endpoint AJAX: cierra la sesión del contrato.
     */
    public static function handle_logout() {
        $nonce = sanitize_text_field( $_POST['nonce'] ?? '' );

        if ( ! wp_verify_nonce( $nonce, 'pc_logout' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce inválido.' ), 403 );
        }

        self::destroy_session();
        wp_send_json_success();
    }

    // ----------------------------------------------------------------
    // Configuración
    // ----------------------------------------------------------------

    /**
     * @return string Google Client ID configurado en opciones.
     */
    public static function get_google_client_id() {
        return get_option( 'pc_google_client_id', '' );
    }

    /**
     * @return bool Si el sistema está listo para autenticar.
     */
    public static function is_configured() {
        return ! empty( self::get_google_client_id() );
    }
}
