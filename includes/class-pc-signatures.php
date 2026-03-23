<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Signatures
 *
 * Responsabilidades:
 * - Validar todos los requisitos para firmar (server-side, sin confiar en frontend).
 * - Insertar registro en pc_signatures.
 * - Actualizar pc_contracts a estado signed.
 * - Registrar log contract_signed.
 *
 * Esta clase es el punto más crítico del sistema.
 * No se puede revertir una firma una vez guardada.
 */
class PC_Signatures {

    /**
     * Ejecuta la firma de un contrato.
     * Valida todo independientemente del frontend.
     *
     * @param  object $contract  Objeto del contrato (debe ser status=pending).
     * @param  array  $session   Sesión autenticada de PC_Auth::get_session().
     * @param  array  $data      Datos sanitizados de PC_Security::sanitize_signature_input().
     * @return true|WP_Error
     */
    public static function sign( $contract, array $session, array $data ) {
        global $wpdb;

        // ---- Validaciones obligatorias ----------------------------------------

        // 1. Contrato existe
        if ( ! $contract || empty( $contract->id ) ) {
            return new WP_Error( 'contract_not_found', __( 'Contrato no encontrado.', 'pylaris-contracts' ) );
        }

        // 2. El contrato debe estar en estado pending
        if ( $contract->status !== 'pending' ) {
            return new WP_Error(
                'not_pending',
                __( 'Este contrato no está disponible para firma.', 'pylaris-contracts' )
            );
        }

        // 3. Verificar que no esté vencido (segunda verificación, por si acaso)
        if ( ! empty( $contract->expires_at ) ) {
            $now     = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
            $expires = new DateTime( $contract->expires_at, new DateTimeZone( 'UTC' ) );
            if ( $now > $expires ) {
                return new WP_Error( 'contract_expired', __( 'Este contrato ya venció.', 'pylaris-contracts' ) );
            }
        }

        // 4. Sesión válida
        if ( empty( $session['google_email'] ) ) {
            return new WP_Error( 'no_session', __( 'Sesión inválida. Iniciá sesión nuevamente.', 'pylaris-contracts' ) );
        }

        // 5. Email de sesión coincide con email del contrato
        $session_email  = strtolower( trim( $session['google_email'] ) );
        $contract_email = strtolower( trim( $contract->client_email ) );

        if ( ! hash_equals( $contract_email, $session_email ) ) {
            return new WP_Error( 'email_mismatch', __( 'La cuenta de Google no coincide con la del contrato.', 'pylaris-contracts' ) );
        }

        // 6. Token del contrato en sesión coincide (verificación de integridad)
        if ( ! hash_equals( $contract->token, $session['contract_token'] ?? '' ) ) {
            return new WP_Error( 'session_token_mismatch', __( 'Error de sesión. Recargá la página.', 'pylaris-contracts' ) );
        }

        // 7. Checkbox marcado
        if ( empty( $data['accepted_checkbox'] ) ) {
            return new WP_Error( 'no_checkbox', __( 'Debés aceptar el acuerdo para continuar.', 'pylaris-contracts' ) );
        }

        // 8. Nombre completo presente
        if ( empty( trim( $data['signed_name'] ?? '' ) ) ) {
            return new WP_Error( 'no_name', __( 'Ingresá tu nombre completo.', 'pylaris-contracts' ) );
        }

        // 9. DNI/CUIT presente
        if ( empty( trim( $data['signed_dni_cuit'] ?? '' ) ) ) {
            return new WP_Error( 'no_dni', __( 'Ingresá tu DNI o CUIT.', 'pylaris-contracts' ) );
        }

        // ---- Persistencia (operación atómica manual) ---------------------------

        $now             = current_time( 'mysql', true );
        $hash_at_sign    = $contract->contract_hash; // Hash del documento exacto que se firmó
        $signed_name     = sanitize_text_field( $data['signed_name'] );
        $signed_dni_cuit = sanitize_text_field( $data['signed_dni_cuit'] );

        // a) Insertar en pc_signatures
        $inserted = $wpdb->insert(
            PC_DB::signatures_table(),
            array(
                'contract_id'                => $contract->id,
                'google_sub'                 => $session['google_sub'] ?? null,
                'google_email'               => $session_email,
                'google_name'                => $session['google_name'] ?? null,
                'ip_address'                 => PC_Helpers::get_client_ip(),
                'user_agent'                 => isset( $_SERVER['HTTP_USER_AGENT'] )
                                                ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] )
                                                : null,
                'accepted_checkbox'          => 1,
                'signed_name'                => $signed_name,
                'signed_dni_cuit'            => $signed_dni_cuit,
                'contract_hash_at_signature' => $hash_at_sign,
                'created_at'                 => $now,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error_signature', __( 'Error al guardar la firma. Intentá nuevamente.', 'pylaris-contracts' ) );
        }

        // b) Actualizar pc_contracts a signed
        $updated = $wpdb->update(
            PC_DB::contracts_table(),
            array(
                'status'               => 'signed',
                'signed_name'          => $signed_name,
                'signed_dni_cuit'      => $signed_dni_cuit,
                'signed_at'            => $now,
                'google_email_verified' => $session_email,
                'updated_at'           => $now,
            ),
            array( 'id' => $contract->id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $updated ) {
            // La firma ya se guardó — no revertir, solo loguear el problema
            PC_DB::log_event( $contract->id, 'contract_signed', array(
                'warning'      => 'signature_inserted_but_contract_update_failed',
                'google_email' => $session_email,
            ) );

            return new WP_Error( 'db_error_update', __( 'Error al actualizar el estado del contrato. Contactá a Pylaris.', 'pylaris-contracts' ) );
        }

        // c) Registrar log
        PC_DB::log_event( $contract->id, 'contract_signed', array(
            'google_email'    => $session_email,
            'google_sub'      => $session['google_sub'] ?? null,
            'signed_name'     => $signed_name,
            'signed_dni_cuit' => $signed_dni_cuit,
            'ip'              => PC_Helpers::get_client_ip(),
            'contract_hash'   => $hash_at_sign,
        ) );

        return true;
    }
}
