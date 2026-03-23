<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Admin
 *
 * Registra los menús del panel de administración de WordPress
 * y encola los assets del área admin.
 *
 * No contiene lógica de negocio — delega a las vistas correspondientes.
 */
class PC_Admin {

    /**
     * Registra el menú principal y submenús del plugin en el admin de WP.
     */
    public function register_menus() {
        add_menu_page(
            __( 'Pylaris Contracts', 'pylaris-contracts' ),
            __( 'Contratos', 'pylaris-contracts' ),
            PC_Security::CAP_ADMIN,
            'pylaris-contracts',
            array( $this, 'render_contracts_list' ),
            'dashicons-media-document',
            56
        );

        add_submenu_page(
            'pylaris-contracts',
            __( 'Todos los contratos', 'pylaris-contracts' ),
            __( 'Todos los contratos', 'pylaris-contracts' ),
            PC_Security::CAP_ADMIN,
            'pylaris-contracts',
            array( $this, 'render_contracts_list' )
        );

        add_submenu_page(
            'pylaris-contracts',
            __( 'Nuevo contrato', 'pylaris-contracts' ),
            __( 'Nuevo contrato', 'pylaris-contracts' ),
            PC_Security::CAP_ADMIN,
            'pylaris-contracts-new',
            array( $this, 'render_contract_create' )
        );

        add_submenu_page(
            'pylaris-contracts',
            __( 'Configuración', 'pylaris-contracts' ),
            __( 'Configuración', 'pylaris-contracts' ),
            PC_Security::CAP_ADMIN,
            'pylaris-contracts-settings',
            array( $this, 'render_settings' )
        );

        add_submenu_page(
            'pylaris-contracts',
            __( 'Logs', 'pylaris-contracts' ),
            __( 'Logs', 'pylaris-contracts' ),
            PC_Security::CAP_ADMIN,
            'pylaris-contracts-logs',
            array( $this, 'render_logs' )
        );
    }

    /**
     * Encola CSS y JS solo en las páginas del plugin.
     *
     * @param string $hook_suffix
     */
    public function enqueue_assets( $hook_suffix ) {
        // Solo cargar en páginas del plugin
        $plugin_pages = array(
            'toplevel_page_pylaris-contracts',
            'contratos_page_pylaris-contracts-new',
            'contratos_page_pylaris-contracts-settings',
            'contratos_page_pylaris-contracts-logs',
        );

        if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'pc-admin',
            PC_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PC_VERSION
        );

        wp_enqueue_script(
            'pc-admin',
            PC_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            PC_VERSION,
            true
        );

        wp_localize_script( 'pc-admin', 'pcAdmin', array(
            'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
            'templateNonce'    => wp_create_nonce( 'pc_generate_template' ),
            'generatingText'   => __( 'Generando...', 'pylaris-contracts' ),
            'generatedText'    => __( 'Template generado correctamente.', 'pylaris-contracts' ),
            'errorText'        => __( 'Error al generar el template.', 'pylaris-contracts' ),
        ) );
    }

    /**
     * Render: listado de contratos.
     */
    public function render_contracts_list() {
        PC_Security::require_admin();
        require_once PC_PLUGIN_DIR . 'admin/views/page-contracts-list.php';
    }

    /**
     * Render: formulario de creación/edición de contrato.
     * La vista detecta internamente si es creación o edición via $_GET['edit'].
     */
    public function render_contract_create() {
        PC_Security::require_admin();
        require_once PC_PLUGIN_DIR . 'admin/views/page-contract-create.php';
    }

    /**
     * Render: configuración del plugin.
     */
    public function render_settings() {
        PC_Security::require_admin();
        require_once PC_PLUGIN_DIR . 'admin/views/page-settings.php';
    }

    public function render_logs() {
        PC_Security::require_admin();
        require_once PC_PLUGIN_DIR . 'admin/views/page-logs.php';
    }
}
