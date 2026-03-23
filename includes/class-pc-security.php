<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Security
 *
 * Responsabilidades:
 * - Verificación de nonces.
 * - Verificación de capacidades de usuario.
 * - Sanitización estándar de inputs del plugin.
 *
 * Todos los endpoints y formularios deben pasar por estos métodos.
 * Nunca procesar datos sin sanitizar.
 */
class PC_Security {

    // Nombres de nonces del plugin
    const NONCE_ADMIN_CONTRACT  = 'pc_admin_contract';
    const NONCE_SIGN_CONTRACT   = 'pc_sign_contract';

    // Capacidad requerida para acceder al panel admin
    const CAP_ADMIN = 'manage_options';

    /**
     * Verifica si el usuario actual tiene permiso de administración del plugin.
     *
     * @return bool
     */
    public static function current_user_can_admin() {
        return current_user_can( self::CAP_ADMIN );
    }

    /**
     * Aborta con un error 403 si el usuario no tiene permisos de admin.
     */
    public static function require_admin() {
        if ( ! self::current_user_can_admin() ) {
            wp_die(
                esc_html__( 'No tenés permiso para realizar esta acción.', 'pylaris-contracts' ),
                403
            );
        }
    }

    /**
     * Genera un nonce para un contexto dado.
     *
     * @param  string $action
     * @return string
     */
    public static function create_nonce( $action ) {
        return wp_create_nonce( $action );
    }

    /**
     * Verifica un nonce. Aborta si no es válido.
     *
     * @param string $nonce
     * @param string $action
     */
    public static function verify_nonce( $nonce, $action ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_die(
                esc_html__( 'Token de seguridad inválido. Por favor recargá la página.', 'pylaris-contracts' ),
                403
            );
        }
    }

    /**
     * Sanitiza los datos del formulario de contrato recibidos desde el admin.
     *
     * @param  array $raw  $_POST crudo.
     * @return array       Datos sanitizados.
     */
    public static function sanitize_contract_input( array $raw ) {
        return array(
            'client_name'      => sanitize_text_field( $raw['client_name'] ?? '' ),
            'client_email'     => PC_Helpers::normalize_email( $raw['client_email'] ?? '' ),
            'client_dni_cuit'  => sanitize_text_field( $raw['client_dni_cuit'] ?? '' ),
            'client_company'   => sanitize_text_field( $raw['client_company'] ?? '' ),
            'project_title'    => sanitize_text_field( $raw['project_title'] ?? '' ),
            'project_scope'    => sanitize_textarea_field( $raw['project_scope'] ?? '' ),
            'project_amount'   => self::sanitize_positive_decimal( $raw['project_amount'] ?? '' ),
            'project_currency' => sanitize_text_field( $raw['project_currency'] ?? '' ),
            'delivery_time'    => sanitize_text_field( $raw['delivery_time'] ?? '' ),
            'revision_rounds'  => absint( $raw['revision_rounds'] ?? 2 ),
            'jurisdiction'     => sanitize_text_field( $raw['jurisdiction'] ?? '' ),
            'contract_html'    => wp_kses_post( $raw['contract_html'] ?? '' ),
            'status'           => sanitize_text_field( $raw['status'] ?? 'draft' ),
            'expires_at'       => sanitize_text_field( $raw['expires_at'] ?? '' ),
        );
    }

    /**
     * Sanitiza los datos del formulario de firma enviados por el cliente.
     *
     * @param  array $raw
     * @return array
     */
    public static function sanitize_signature_input( array $raw ) {
        return array(
            'token'            => sanitize_text_field( $raw['token'] ?? '' ),
            'signed_name'      => sanitize_text_field( $raw['signed_name'] ?? '' ),
            'signed_dni_cuit'  => sanitize_text_field( $raw['signed_dni_cuit'] ?? '' ),
            'accepted_checkbox' => ! empty( $raw['accepted_checkbox'] ),
        );
    }

    /**
     * Sanitiza un decimal positivo.
     *
     * @param  mixed $value
     * @return float|false  Decimal positivo o false si inválido.
     */
    private static function sanitize_positive_decimal( $value ) {
        $value = floatval( str_replace( ',', '.', $value ) );

        if ( $value <= 0 ) {
            return false;
        }

        return $value;
    }
}
