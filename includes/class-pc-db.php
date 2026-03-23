<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_DB
 *
 * Centraliza el acceso a las tablas del plugin.
 * No ejecuta lógica de negocio — solo provee nombres de tablas
 * y métodos de consulta base reutilizables.
 */
class PC_DB {

    /**
     * Nombre de la tabla de contratos.
     *
     * @return string
     */
    public static function contracts_table() {
        global $wpdb;
        return $wpdb->prefix . 'pc_contracts';
    }

    /**
     * Nombre de la tabla de firmas.
     *
     * @return string
     */
    public static function signatures_table() {
        global $wpdb;
        return $wpdb->prefix . 'pc_signatures';
    }

    /**
     * Nombre de la tabla de logs.
     *
     * @return string
     */
    public static function logs_table() {
        global $wpdb;
        return $wpdb->prefix . 'pc_contract_logs';
    }

    /**
     * Busca un contrato por token.
     *
     * @param  string $token
     * @return object|null
     */
    public static function get_contract_by_token( $token ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE token = %s LIMIT 1',
                self::contracts_table(),
                $token
            )
        );
    }

    /**
     * Busca un contrato por ID.
     *
     * @param  int $contract_id
     * @return object|null
     */
    public static function get_contract_by_id( $contract_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM %i WHERE id = %d LIMIT 1',
                self::contracts_table(),
                (int) $contract_id
            )
        );
    }

    /**
     * Registra un evento en la tabla de logs.
     *
     * @param int    $contract_id
     * @param string $event_type
     * @param mixed  $event_data  Array o string. Se serializa a JSON si es array.
     * @return int|false          Rows afectadas o false en error.
     */
    public static function log_event( $contract_id, $event_type, $event_data = null ) {
        global $wpdb;

        if ( is_array( $event_data ) ) {
            $event_data = wp_json_encode( $event_data );
        }

        return $wpdb->insert(
            self::logs_table(),
            array(
                'contract_id' => (int) $contract_id,
                'event_type'  => sanitize_text_field( $event_type ),
                'event_data'  => $event_data,
                'created_at'  => current_time( 'mysql', true ), // UTC
            ),
            array( '%d', '%s', '%s', '%s' )
        );
    }

    /**
     * Actualiza el estado de un contrato.
     * No hace validación de transición — esa responsabilidad es de PC_Contracts.
     *
     * @param  int    $contract_id
     * @param  string $new_status
     * @return int|false
     */
    public static function update_status( $contract_id, $new_status ) {
        global $wpdb;

        return $wpdb->update(
            self::contracts_table(),
            array(
                'status'     => $new_status,
                'updated_at' => current_time( 'mysql', true ),
            ),
            array( 'id' => (int) $contract_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }
}
