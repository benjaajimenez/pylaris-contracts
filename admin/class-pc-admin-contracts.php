<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Admin_Contracts
 *
 * Controlador del área admin para contratos.
 * Responsabilidades:
 * - Procesar POST de crear/editar/cambiar estado
 * - Pasar datos a PC_Contracts (lógica de negocio)
 * - Preparar datos para las vistas admin
 *
 * No contiene lógica de negocio — eso vive en PC_Contracts.
 */
class PC_Admin_Contracts {

    /**
     * Procesa el formulario de creación de contrato.
     * Retorna array con resultado para la vista.
     *
     * @return array { success: bool, message: string, contract_id: int|null }
     */
    public static function handle_create() {
        if ( ! isset( $_POST['pc_action'] ) || $_POST['pc_action'] !== 'create_contract' ) {
            return null;
        }

        PC_Security::require_admin();
        PC_Security::verify_nonce( $_POST['_wpnonce'] ?? '', PC_Security::NONCE_ADMIN_CONTRACT );

        // Cargar clase de contratos si no está cargada
        if ( ! class_exists( 'PC_Contracts' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-contracts.php';
        }

        $data   = PC_Security::sanitize_contract_input( $_POST );
        $result = PC_Contracts::create( $data );

        if ( is_wp_error( $result ) ) {
            return array(
                'success'     => false,
                'message'     => $result->get_error_message(),
                'contract_id' => null,
                'input'       => $data,
            );
        }

        return array(
            'success'     => true,
            'message'     => sprintf(
                __( 'Contrato creado correctamente. ID: %d', 'pylaris-contracts' ),
                $result
            ),
            'contract_id' => $result,
            'input'       => array(),
        );
    }

    /**
     * Procesa el formulario de edición de contrato.
     *
     * @param  int $contract_id
     * @return array|null
     */
    public static function handle_update( $contract_id ) {
        if ( ! isset( $_POST['pc_action'] ) || $_POST['pc_action'] !== 'update_contract' ) {
            return null;
        }

        PC_Security::require_admin();
        PC_Security::verify_nonce( $_POST['_wpnonce'] ?? '', PC_Security::NONCE_ADMIN_CONTRACT );

        if ( ! class_exists( 'PC_Contracts' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-contracts.php';
        }

        $data   = PC_Security::sanitize_contract_input( $_POST );
        $result = PC_Contracts::update( (int) $contract_id, $data );

        if ( is_wp_error( $result ) ) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
                'input'   => $data,
            );
        }

        return array(
            'success' => true,
            'message' => __( 'Contrato actualizado correctamente.', 'pylaris-contracts' ),
            'input'   => array(),
        );
    }

    /**
     * Procesa cambio de estado desde el listado o la vista de edición.
     *
     * @return array|null
     */
    public static function handle_status_change() {
        if ( ! isset( $_POST['pc_action'] ) || $_POST['pc_action'] !== 'change_status' ) {
            return null;
        }

        PC_Security::require_admin();
        PC_Security::verify_nonce( $_POST['_wpnonce'] ?? '', PC_Security::NONCE_ADMIN_CONTRACT );

        if ( ! class_exists( 'PC_Contracts' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-contracts.php';
        }

        $contract_id = absint( $_POST['contract_id'] ?? 0 );
        $new_status  = sanitize_text_field( $_POST['new_status'] ?? '' );

        if ( ! $contract_id ) {
            return array( 'success' => false, 'message' => __( 'ID de contrato inválido.', 'pylaris-contracts' ) );
        }

        $result = PC_Contracts::change_status( $contract_id, $new_status );

        if ( is_wp_error( $result ) ) {
            return array( 'success' => false, 'message' => $result->get_error_message() );
        }

        return array( 'success' => true, 'message' => __( 'Estado actualizado correctamente.', 'pylaris-contracts' ) );
    }

    /**
     * Procesa el envío del link del contrato al cliente por email.
     *
     * @return array|null
     */
    public static function handle_send_link() {
        if ( ! isset( $_POST['pc_action'] ) || $_POST['pc_action'] !== 'send_link' ) {
            return null;
        }

        PC_Security::require_admin();
        PC_Security::verify_nonce( $_POST['_wpnonce'] ?? '', PC_Security::NONCE_ADMIN_CONTRACT );

        if ( ! class_exists( 'PC_Mails' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-mails.php';
        }

        $contract_id = absint( $_POST['contract_id'] ?? 0 );
        $contract    = PC_DB::get_contract_by_id( $contract_id );

        if ( ! $contract || $contract->status !== 'pending' ) {
            return array( 'success' => false, 'message' => __( 'Contrato no disponible para envío.', 'pylaris-contracts' ) );
        }

        $sent = PC_Mails::send_contract_link( $contract );

        return array(
            'success' => $sent,
            'message' => $sent
                ? sprintf( __( 'Link enviado a %s.', 'pylaris-contracts' ), $contract->client_email )
                : __( 'Error al enviar el email. Verificá la configuración SMTP.', 'pylaris-contracts' ),
        );
    }

    /**
     * Genera el HTML del contrato desde el template base con los datos del formulario.
     * Se llama via AJAX desde el formulario de creación.
     */
    public static function register_template_ajax() {
        add_action( 'wp_ajax_pc_generate_template', array( __CLASS__, 'handle_generate_from_template' ) );
    }

    public static function handle_generate_from_template() {
        PC_Security::require_admin();
        check_ajax_referer( 'pc_generate_template', 'nonce' );

        if ( ! class_exists( 'PC_Template' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-template.php';
        }

        $data = PC_Security::sanitize_contract_input( $_POST );

        $data['contract_number'] = sanitize_text_field( $_POST['contract_number'] ?? 'PC-XXXX-XXXX' );

        $html = PC_Template::render( $data );

        wp_send_json_success( array( 'html' => $html ) );
    }

    /**
     * Prepara los datos para la vista del listado de contratos.
     *
     * @return array
     */
    public static function prepare_list_data() {
        if ( ! class_exists( 'PC_Contracts' ) ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-contracts.php';
        }

        $args = array(
            'status'   => sanitize_text_field( $_GET['status'] ?? '' ),
            'search'   => sanitize_text_field( $_GET['s'] ?? '' ),
            'per_page' => 20,
            'page'     => max( 1, absint( $_GET['paged'] ?? 1 ) ),
            'orderby'  => sanitize_text_field( $_GET['orderby'] ?? 'created_at' ),
            'order'    => sanitize_text_field( $_GET['order'] ?? 'DESC' ),
        );

        return PC_Contracts::get_list( $args );
    }

    /**
     * Construye la URL del contrato público para compartir con el cliente.
     *
     * @param  string $token
     * @return string
     */
    public static function get_contract_url( $token ) {
        return home_url( '/c/' . $token );
    }

    /**
     * Genera los labels legibles para cada estado.
     *
     * @return array
     */
    public static function get_status_labels() {
        return array(
            'draft'     => __( 'Borrador', 'pylaris-contracts' ),
            'pending'   => __( 'Pendiente', 'pylaris-contracts' ),
            'signed'    => __( 'Firmado', 'pylaris-contracts' ),
            'expired'   => __( 'Vencido', 'pylaris-contracts' ),
            'cancelled' => __( 'Cancelado', 'pylaris-contracts' ),
        );
    }
}
