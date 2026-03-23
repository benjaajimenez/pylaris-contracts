<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Helpers
 *
 * Funciones utilitarias del plugin.
 * No tiene estado propio — todos los métodos son estáticos.
 */
class PC_Helpers {

    /**
     * Genera un número de contrato único con formato PC-{AÑO}-{SECUENCIA}.
     * Ejemplo: PC-2026-0001
     *
     * @return string
     */
    public static function generate_contract_number() {
        global $wpdb;

        $year  = gmdate( 'Y' );
        $table = PC_DB::contracts_table();

        $last = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT contract_number FROM %i WHERE contract_number LIKE %s ORDER BY id DESC LIMIT 1",
                $table,
                "PC-{$year}-%"
            )
        );

        if ( $last ) {
            $parts    = explode( '-', $last );
            $sequence = (int) end( $parts ) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf( 'PC-%s-%04d', $year, $sequence );
    }

    /**
     * Genera un token único criptográficamente seguro.
     *
     * @return string
     */
    public static function generate_token() {
        return bin2hex( random_bytes( 32 ) ); // 64 chars hex
    }

    /**
     * Genera el hash SHA-256 del HTML del contrato.
     * Siempre server-side. Nunca confiar en valor del frontend.
     *
     * @param  string $contract_html
     * @return string Hash de 64 caracteres.
     */
    public static function generate_contract_hash( $contract_html ) {
        return hash( 'sha256', $contract_html );
    }

    /**
     * Normaliza un email a minúsculas y lo valida.
     *
     * @param  string $email
     * @return string|false Email normalizado o false si es inválido.
     */
    public static function normalize_email( $email ) {
        $email = strtolower( trim( $email ) );

        if ( ! is_email( $email ) ) {
            return false;
        }

        return $email;
    }

    /**
     * Devuelve la IP del visitante actual.
     * Considera proxies básicos pero no confía ciegamente en headers.
     *
     * @return string
     */
    public static function get_client_ip() {
        $ip = '';

        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $forwarded = explode( ',', sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
            $ip        = trim( $forwarded[0] );
        } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
        }

        return $ip;
    }

    /**
     * Valida que un estado sea uno de los valores permitidos.
     *
     * @param  string $status
     * @return bool
     */
    public static function is_valid_status( $status ) {
        return in_array( $status, array( 'draft', 'pending', 'signed', 'expired', 'cancelled' ), true );
    }

    /**
     * Verifica si un contrato puede ser editado según su estado.
     *
     * @param  string $status
     * @return bool
     */
    public static function is_editable_status( $status ) {
        return in_array( $status, array( 'draft', 'pending' ), true );
    }

    /**
     * Verifica si una transición de estado es válida.
     *
     * Transiciones permitidas:
     * draft     → pending
     * pending   → signed
     * pending   → expired
     * pending   → cancelled
     *
     * @param  string $from Estado actual.
     * @param  string $to   Estado destino.
     * @return bool
     */
    public static function is_valid_transition( $from, $to ) {
        $allowed = array(
            'draft'   => array( 'pending' ),
            'pending' => array( 'signed', 'expired', 'cancelled' ),
        );

        return isset( $allowed[ $from ] ) && in_array( $to, $allowed[ $from ], true );
    }

    /**
     * Formatea una fecha UTC a zona horaria local de WordPress.
     *
     * @param  string $datetime_utc  Fecha en formato MySQL UTC.
     * @param  string $format        Formato de salida (default: d/m/Y H:i).
     * @return string
     */
    public static function format_date( $datetime_utc, $format = 'd/m/Y H:i' ) {
        if ( empty( $datetime_utc ) ) {
            return '';
        }

        $dt = new DateTime( $datetime_utc, new DateTimeZone( 'UTC' ) );
        $dt->setTimezone( new DateTimeZone( wp_timezone_string() ) );

        return $dt->format( $format );
    }
}
