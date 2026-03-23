<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Public
 *
 * Maneja el acceso público al sistema de contratos.
 * Responsabilidades:
 * - Registrar rewrite rules para /c/{token}
 * - Resolver la request: estado del contrato + estado de autenticación
 * - Decidir qué vista renderizar
 * - Encolar assets del frontend
 *
 * No contiene lógica de negocio — delega a PC_DB, PC_Auth y las vistas.
 */
class PC_Public {

    /**
     * Registra la regla de rewrite para /c/{token}
     */
    public function register_rewrite_rules() {
        add_rewrite_rule(
            '^c/([a-zA-Z0-9]+)/?$',
            'index.php?pc_contract_token=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^c/([a-zA-Z0-9]+)/constancia/?$',
            'index.php?pc_contract_token=$matches[1]&pc_action=constancia',
            'top'
        );
    }

    public function add_query_vars( $vars ) {
        $vars[] = 'pc_contract_token';
        $vars[] = 'pc_action';
        return $vars;
    }

    /**
     * Intercepta la request cuando hay un token de contrato en la URL.
     * Punto central de decisión: qué pantalla mostrar.
     */
    public function handle_contract_request() {
        $token = get_query_var( 'pc_contract_token' );

        if ( empty( $token ) ) {
            return;
        }

        // Sanitizar: solo alfanumérico
        $token = preg_replace( '/[^a-zA-Z0-9]/', '', $token );

        if ( empty( $token ) ) {
            $this->render_view( 'contract-invalid' );
            exit;
        }

        // Cargar dependencias necesarias
        require_once PC_PLUGIN_DIR . 'includes/class-pc-auth.php';

        // Manejar descarga de constancia
        $pc_action = get_query_var( 'pc_action' );
        if ( 'constancia' === $pc_action ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-pdf.php';
            PC_PDF::handle_download_request( $token );
            exit;
        }

        $contract = PC_DB::get_contract_by_token( $token );

        // Token inexistente
        if ( ! $contract ) {
            $this->render_view( 'contract-invalid' );
            exit;
        }

        // Detectar vencimiento dinámico
        if ( 'pending' === $contract->status && ! empty( $contract->expires_at ) ) {
            $now     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
            $expires = new DateTime( $contract->expires_at, new DateTimeZone( 'UTC' ) );

            if ( $now > $expires ) {
                PC_DB::update_status( $contract->id, 'expired' );
                PC_DB::log_event( $contract->id, 'contract_expired', array(
                    'detected_at' => current_time( 'mysql', true ),
                ) );
                $contract->status = 'expired';
            }
        }

        // Resolver por estado del contrato
        switch ( $contract->status ) {

            case 'expired':
                $this->render_view( 'contract-expired', array( 'contract' => $contract ) );
                exit;

            case 'signed':
                $this->render_view( 'contract-signed', array( 'contract' => $contract ) );
                exit;

            case 'cancelled':
            case 'draft':
                $this->render_view( 'contract-invalid' );
                exit;

            case 'pending':
                $this->resolve_pending_contract( $contract );
                exit;
        }
    }

    /**
     * Resuelve un contrato en estado pending.
     * Acá es donde entra la lógica de autenticación.
     *
     * @param object $contract
     */
    private function resolve_pending_contract( $contract ) {
        require_once PC_PLUGIN_DIR . 'includes/class-pc-auth.php';

        // Verificar si hay sesión válida para este contrato
        $session = PC_Auth::get_session( $contract->token );

        // Sin sesión → pedir login
        if ( ! $session ) {
            $this->render_view( 'contract-login-required', array( 'contract' => $contract ) );
            return;
        }

        // Sesión existe pero email incorrecto (caso raro, defensa en profundidad)
        if ( ! PC_Auth::session_matches_contract( $contract, $session ) ) {
            PC_Auth::destroy_session();
            $this->render_view( 'contract-access-denied', array(
                'contract'     => $contract,
                'google_email' => $session['google_email'] ?? '',
            ) );
            return;
        }

        // Sesión válida y email correcto → procesar firma o mostrar contrato

        // ¿Viene un intento de firma?
        if ( isset( $_POST['pc_action'] ) && 'sign_contract' === $_POST['pc_action'] ) {
            $this->handle_sign_attempt( $contract, $session );
            return;
        }

        // Mostrar contrato
        PC_DB::log_event( $contract->id, 'contract_viewed', array(
            'google_email' => $session['google_email'] ?? '',
            'google_sub'   => $session['google_sub'] ?? '',
            'ip'           => PC_Helpers::get_client_ip(),
        ) );

        $this->render_view( 'contract-view', array(
            'contract'     => $contract,
            'google_email' => $session['google_email'],
            'google_name'  => $session['google_name'],
            'sign_error'   => null,
        ) );
    }

    /**
     * Procesa el intento de firma del contrato.
     * Valida todo server-side independientemente del frontend.
     *
     * @param object $contract
     * @param array  $session
     */
    private function handle_sign_attempt( $contract, array $session ) {
        require_once PC_PLUGIN_DIR . 'includes/class-pc-signatures.php';

        // Verificar nonce
        $nonce = sanitize_text_field( $_POST['_wpnonce'] ?? '' );
        if ( ! wp_verify_nonce( $nonce, PC_Security::NONCE_SIGN_CONTRACT ) ) {
            $this->render_view( 'contract-view', array(
                'contract'     => $contract,
                'google_email' => $session['google_email'],
                'google_name'  => $session['google_name'],
                'sign_error'   => array( 'message' => __( 'Token de seguridad inválido. Recargá la página.', 'pylaris-contracts' ) ),
            ) );
            return;
        }

        $data = PC_Security::sanitize_signature_input( $_POST );

        $result = PC_Signatures::sign( $contract, $session, $data );

        if ( is_wp_error( $result ) ) {
            $this->render_view( 'contract-view', array(
                'contract'     => $contract,
                'google_email' => $session['google_email'],
                'google_name'  => $session['google_name'],
                'sign_error'   => array( 'message' => $result->get_error_message() ),
            ) );
            return;
        }

        // Firma exitosa → destruir sesión, enviar mails, mostrar éxito
        PC_Auth::destroy_session();

        // Recargar contrato actualizado
        $signed_contract = PC_DB::get_contract_by_id( $contract->id );
        $contract_final  = $signed_contract ?: $contract;

        // Enviar emails (no bloquea el flujo si fallan)
        require_once PC_PLUGIN_DIR . 'includes/class-pc-mails.php';
        PC_Mails::send_signature_confirmation( $contract_final );
        PC_Mails::send_internal_notification( $contract_final );

        $this->render_view( 'contract-signed', array(
            'contract' => $contract_final,
        ) );
    }

    /**
     * Encola CSS y JS del frontend solo en páginas de contrato.
     */
    public function enqueue_assets() {
        $token = get_query_var( 'pc_contract_token' );

        if ( empty( $token ) ) {
            return;
        }

        wp_enqueue_style(
            'pc-public',
            PC_PLUGIN_URL . 'assets/css/public.css',
            array(),
            PC_VERSION
        );

        // Google Identity Services — solo si está configurado
        require_once PC_PLUGIN_DIR . 'includes/class-pc-auth.php';

        if ( PC_Auth::is_configured() ) {
            wp_enqueue_script(
                'google-identity',
                'https://accounts.google.com/gsi/client',
                array(),
                null,
                false
            );
            // Cuando la librería de Google cargue, llama a pcInitGoogle()
            add_filter( 'script_loader_tag', function( $tag, $handle ) {
                if ( 'google-identity' === $handle ) {
                    $tag = str_replace( '<script ', '<script onload="pcInitGoogle()" ', $tag );
                }
                return $tag;
            }, 10, 2 );
        }

        wp_enqueue_script(
            'pc-public',
            PC_PLUGIN_URL . 'assets/js/public.js',
            array(),
            PC_VERSION,
            true
        );

        // Pasar datos al JS
        wp_localize_script( 'pc-public', 'pcData', array(
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'authNonce'      => wp_create_nonce( 'pc_google_auth' ),
            'logoutNonce'    => wp_create_nonce( 'pc_logout' ),
            'googleClientId' => PC_Auth::get_google_client_id(),
            'contractToken'  => preg_replace( '/[^a-zA-Z0-9]/', '', $token ),
        ) );
    }

    /**
     * Renderiza una vista del directorio public/views/.
     *
     * @param string $view
     * @param array  $data
     */
    private function render_view( $view, $data = array() ) {
        $file = PC_PLUGIN_DIR . 'public/views/' . $view . '.php';

        if ( ! file_exists( $file ) ) {
            wp_die( esc_html__( 'Vista no encontrada: ', 'pylaris-contracts' ) . esc_html( $view ) );
        }

        if ( ! wp_style_is( 'pc-public', 'enqueued' ) ) {
            wp_enqueue_style(
                'pc-public',
                PC_PLUGIN_URL . 'assets/css/public.css',
                array(),
                PC_VERSION
            );
        }

        if ( ! empty( $data ) ) {
            extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
        }

        include $file;
    }
}
