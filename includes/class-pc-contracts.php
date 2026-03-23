<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Contracts
 *
 * Lógica de negocio central del sistema.
 * Responsabilidades:
 * - Crear contratos (validación + persistencia + logs)
 * - Actualizar contratos (con restricciones de estado)
 * - Cambiar estados (con validación de transiciones)
 * - Listar contratos para el admin
 *
 * No renderiza vistas. No maneja requests HTTP directamente.
 * Recibe datos ya sanitizados desde PC_Admin_Contracts.
 */
class PC_Contracts {

    /**
     * Crea un nuevo contrato.
     *
     * @param  array $data Datos sanitizados por PC_Security::sanitize_contract_input().
     * @return int|WP_Error ID del contrato creado o WP_Error.
     */
    public static function create( array $data ) {
        global $wpdb;

        $validation = self::validate_required_fields( $data );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        // Validar que el email sea correcto
        if ( false === $data['client_email'] ) {
            return new WP_Error( 'invalid_email', __( 'El email del cliente no es válido.', 'pylaris-contracts' ) );
        }

        // Validar monto
        if ( false === $data['project_amount'] ) {
            return new WP_Error( 'invalid_amount', __( 'El monto del proyecto debe ser un número positivo.', 'pylaris-contracts' ) );
        }

        // Validar estado inicial
        $status = $data['status'] ?? 'draft';
        if ( ! in_array( $status, array( 'draft', 'pending' ), true ) ) {
            $status = 'draft';
        }

        $now              = current_time( 'mysql', true );
        $contract_number  = PC_Helpers::generate_contract_number();
        $token            = PC_Helpers::generate_token();
        $contract_hash    = PC_Helpers::generate_contract_hash( $data['contract_html'] );

        $row = array(
            'contract_number'   => $contract_number,
            'token'             => $token,
            'client_name'       => $data['client_name'],
            'client_email'      => $data['client_email'],
            'client_dni_cuit'   => $data['client_dni_cuit'],
            'client_company'    => $data['client_company'] ?: null,
            'project_title'     => $data['project_title'] ?: null,
            'project_scope'     => $data['project_scope'],
            'project_amount'    => $data['project_amount'],
            'project_currency'  => strtoupper( $data['project_currency'] ),
            'delivery_time'     => $data['delivery_time'],
            'revision_rounds'   => (int) $data['revision_rounds'],
            'jurisdiction'      => $data['jurisdiction'],
            'status'            => $status,
            'contract_html'     => $data['contract_html'],
            'contract_hash'     => $contract_hash,
            'expires_at'        => ! empty( $data['expires_at'] ) ? $data['expires_at'] : null,
            'created_at'        => $now,
            'updated_at'        => $now,
        );

        $formats = array(
            '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s', '%f', '%s', '%s', '%d',
            '%s', '%s', '%s', '%s', '%s', '%s',
            '%s', '%s',
        );

        $inserted = $wpdb->insert( PC_DB::contracts_table(), $row, $formats );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', __( 'Error al guardar el contrato en la base de datos.', 'pylaris-contracts' ) );
        }

        $contract_id = (int) $wpdb->insert_id;

        PC_DB::log_event( $contract_id, 'contract_created', array(
            'contract_number' => $contract_number,
            'status'          => $status,
            'created_by'      => get_current_user_id(),
        ) );

        return $contract_id;
    }

    /**
     * Actualiza un contrato existente.
     *
     * @param  int   $contract_id
     * @param  array $data Datos sanitizados.
     * @return true|WP_Error
     */
    public static function update( $contract_id, array $data ) {
        global $wpdb;

        $contract = PC_DB::get_contract_by_id( $contract_id );

        if ( ! $contract ) {
            return new WP_Error( 'not_found', __( 'Contrato no encontrado.', 'pylaris-contracts' ) );
        }

        if ( ! PC_Helpers::is_editable_status( $contract->status ) ) {
            return new WP_Error(
                'not_editable',
                sprintf(
                    __( 'El contrato en estado "%s" no puede ser editado.', 'pylaris-contracts' ),
                    $contract->status
                )
            );
        }

        $validation = self::validate_required_fields( $data );
        if ( is_wp_error( $validation ) ) {
            return $validation;
        }

        if ( false === $data['client_email'] ) {
            return new WP_Error( 'invalid_email', __( 'El email del cliente no es válido.', 'pylaris-contracts' ) );
        }

        if ( false === $data['project_amount'] ) {
            return new WP_Error( 'invalid_amount', __( 'El monto del proyecto debe ser un número positivo.', 'pylaris-contracts' ) );
        }

        // Recalcular hash si cambió el HTML
        $new_hash = $contract->contract_hash;
        if ( $data['contract_html'] !== $contract->contract_html ) {
            $new_hash = PC_Helpers::generate_contract_hash( $data['contract_html'] );
        }

        $row = array(
            'client_name'      => $data['client_name'],
            'client_email'     => $data['client_email'],
            'client_dni_cuit'  => $data['client_dni_cuit'],
            'client_company'   => $data['client_company'] ?: null,
            'project_title'    => $data['project_title'] ?: null,
            'project_scope'    => $data['project_scope'],
            'project_amount'   => $data['project_amount'],
            'project_currency' => strtoupper( $data['project_currency'] ),
            'delivery_time'    => $data['delivery_time'],
            'revision_rounds'  => (int) $data['revision_rounds'],
            'jurisdiction'     => $data['jurisdiction'],
            'contract_html'    => $data['contract_html'],
            'contract_hash'    => $new_hash,
            'expires_at'       => ! empty( $data['expires_at'] ) ? $data['expires_at'] : null,
            'updated_at'       => current_time( 'mysql', true ),
        );

        $formats = array(
            '%s', '%s', '%s', '%s', '%s',
            '%s', '%f', '%s', '%s', '%d',
            '%s', '%s', '%s', '%s', '%s',
        );

        $updated = $wpdb->update(
            PC_DB::contracts_table(),
            $row,
            array( 'id' => $contract_id ),
            $formats,
            array( '%d' )
        );

        if ( false === $updated ) {
            return new WP_Error( 'db_error', __( 'Error al actualizar el contrato.', 'pylaris-contracts' ) );
        }

        PC_DB::log_event( $contract_id, 'contract_updated', array(
            'updated_by'  => get_current_user_id(),
            'hash_changed' => ( $new_hash !== $contract->contract_hash ),
        ) );

        return true;
    }

    /**
     * Cambia el estado de un contrato validando la transición.
     *
     * @param  int    $contract_id
     * @param  string $new_status
     * @return true|WP_Error
     */
    public static function change_status( $contract_id, $new_status ) {
        $contract = PC_DB::get_contract_by_id( $contract_id );

        if ( ! $contract ) {
            return new WP_Error( 'not_found', __( 'Contrato no encontrado.', 'pylaris-contracts' ) );
        }

        if ( ! PC_Helpers::is_valid_status( $new_status ) ) {
            return new WP_Error( 'invalid_status', __( 'Estado no válido.', 'pylaris-contracts' ) );
        }

        if ( $contract->status === $new_status ) {
            return true; // Ya está en ese estado, no es error.
        }

        if ( ! PC_Helpers::is_valid_transition( $contract->status, $new_status ) ) {
            return new WP_Error(
                'invalid_transition',
                sprintf(
                    __( 'No se puede pasar de "%s" a "%s".', 'pylaris-contracts' ),
                    $contract->status,
                    $new_status
                )
            );
        }

        $result = PC_DB::update_status( $contract_id, $new_status );

        if ( false === $result ) {
            return new WP_Error( 'db_error', __( 'Error al actualizar el estado.', 'pylaris-contracts' ) );
        }

        PC_DB::log_event( $contract_id, 'contract_status_changed', array(
            'from'       => $contract->status,
            'to'         => $new_status,
            'changed_by' => get_current_user_id(),
        ) );

        return true;
    }

    /**
     * Obtiene la lista de contratos para el admin con filtros y paginación.
     *
     * @param  array $args {
     *     @type string $status    Filtrar por estado. '' = todos.
     *     @type string $search    Buscar en client_name y client_email.
     *     @type int    $per_page  Resultados por página. Default 20.
     *     @type int    $page      Página actual. Default 1.
     *     @type string $orderby   Campo para ordenar. Default 'created_at'.
     *     @type string $order     ASC o DESC. Default 'DESC'.
     * }
     * @return array { items: array, total: int, pages: int }
     */
    public static function get_list( array $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'   => '',
            'search'   => '',
            'per_page' => 20,
            'page'     => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args( $args, $defaults );

        $table  = PC_DB::contracts_table();
        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) && PC_Helpers::is_valid_status( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[]  = '(client_name LIKE %s OR client_email LIKE %s OR contract_number LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Whitelist de columnas para ORDER BY
        $allowed_orderby = array( 'created_at', 'updated_at', 'client_name', 'status', 'contract_number' );
        $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

        $per_page = max( 1, (int) $args['per_page'] );
        $page     = max( 1, (int) $args['page'] );
        $offset   = ( $page - 1 ) * $per_page;

        // Total
        $count_sql = "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}";
        if ( ! empty( $values ) ) {
            $count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore
        }
        $total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore

        // Items
        $query_sql = "SELECT id, contract_number, client_name, client_email, client_company, project_title, project_amount, project_currency, status, created_at, signed_at, expires_at FROM `{$table}` WHERE {$where_sql} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";
        $query_values = array_merge( $values, array( $per_page, $offset ) );
        $items = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_values ) ); // phpcs:ignore

        return array(
            'items' => $items ?: array(),
            'total' => $total,
            'pages' => $total > 0 ? (int) ceil( $total / $per_page ) : 1,
        );
    }

    /**
     * Valida que todos los campos obligatorios estén presentes y no vacíos.
     *
     * @param  array $data
     * @return true|WP_Error
     */
    private static function validate_required_fields( array $data ) {
        $required = array(
            'client_name'      => __( 'Nombre del cliente', 'pylaris-contracts' ),
            'client_email'     => __( 'Email del cliente', 'pylaris-contracts' ),
            'client_dni_cuit'  => __( 'DNI / CUIT del cliente', 'pylaris-contracts' ),
            'project_scope'    => __( 'Alcance del proyecto', 'pylaris-contracts' ),
            'project_amount'   => __( 'Monto del proyecto', 'pylaris-contracts' ),
            'project_currency' => __( 'Moneda', 'pylaris-contracts' ),
            'delivery_time'    => __( 'Plazo de entrega', 'pylaris-contracts' ),
            'jurisdiction'     => __( 'Jurisdicción', 'pylaris-contracts' ),
            'contract_html'    => __( 'HTML del contrato', 'pylaris-contracts' ),
        );

        $missing = array();
        foreach ( $required as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                $missing[] = $label;
            }
        }

        if ( ! empty( $missing ) ) {
            return new WP_Error(
                'missing_fields',
                sprintf(
                    __( 'Campos obligatorios incompletos: %s', 'pylaris-contracts' ),
                    implode( ', ', $missing )
                )
            );
        }

        return true;
    }
}
