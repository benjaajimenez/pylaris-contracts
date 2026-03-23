<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Loader
 *
 * Orquesta la inicialización del plugin.
 * Responsabilidades:
 * - Cargar las clases necesarias según el contexto (admin / public).
 * - Registrar los hooks de WordPress.
 *
 * No contiene lógica de negocio.
 */
class PC_Loader {

    /**
     * Colección de hooks de acción registrados.
     *
     * @var array
     */
    private $actions = array();

    /**
     * Colección de hooks de filtro registrados.
     *
     * @var array
     */
    private $filters = array();

    /**
     * Punto de entrada principal del plugin.
     */
    public function run() {
        $this->load_dependencies();
        $this->register_hooks();
        $this->execute_hooks();
    }

    /**
     * Carga las clases según el contexto de ejecución.
     */
    private function load_dependencies() {
        // Infraestructura — siempre disponible
        require_once PC_PLUGIN_DIR . 'includes/class-pc-db.php';
        require_once PC_PLUGIN_DIR . 'includes/class-pc-helpers.php';
        require_once PC_PLUGIN_DIR . 'includes/class-pc-security.php';
        require_once PC_PLUGIN_DIR . 'includes/class-pc-auth.php';

        // AJAX hooks disponibles en todos los contextos (admin-ajax.php)
        PC_Auth::register_ajax_hooks();

        if ( is_admin() ) {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-contracts.php';
            require_once PC_PLUGIN_DIR . 'includes/class-pc-template.php';
            require_once PC_PLUGIN_DIR . 'includes/class-pc-mails.php';
            require_once PC_PLUGIN_DIR . 'admin/class-pc-admin.php';
            require_once PC_PLUGIN_DIR . 'admin/class-pc-admin-contracts.php';
            PC_Admin_Contracts::register_template_ajax();
        } else {
            require_once PC_PLUGIN_DIR . 'includes/class-pc-signatures.php';
            require_once PC_PLUGIN_DIR . 'includes/class-pc-mails.php';
            require_once PC_PLUGIN_DIR . 'includes/class-pc-pdf.php';
            require_once PC_PLUGIN_DIR . 'public/class-pc-public.php';
        }
    }

    /**
     * Registra los hooks de cada módulo.
     */
    private function register_hooks() {
        if ( is_admin() ) {
            $admin = new PC_Admin();
            $this->add_action( 'admin_menu',            $admin, 'register_menus' );
            $this->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
        } else {
            $public = new PC_Public();
            $this->add_action( 'init',                  $public, 'register_rewrite_rules' );
            $this->add_action( 'template_redirect',     $public, 'handle_contract_request' );
            $this->add_filter( 'query_vars',            $public, 'add_query_vars' );
            $this->add_action( 'wp_enqueue_scripts',    $public, 'enqueue_assets' );
        }
    }

    /**
     * Ejecuta todos los hooks registrados.
     */
    private function execute_hooks() {
        foreach ( $this->actions as $hook ) {
            add_action(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ( $this->filters as $hook ) {
            add_filter(
                $hook['hook'],
                array( $hook['component'], $hook['callback'] ),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }

    /**
     * Agrega una acción a la cola.
     */
    private function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }

    /**
     * Agrega un filtro a la cola.
     */
    private function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args,
        );
    }
}
