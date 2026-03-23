<?php
/**
 * Plugin Name: Pylaris Contracts
 * Plugin URI:  https://pylaris.com
 * Description: Sistema privado de contratos digitales para Pylaris.
 * Version:     1.0.0
 * Author:      Pylaris
 * Author URI:  https://pylaris.com
 * Text Domain: pylaris-contracts
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

// Constantes del plugin
define( 'PC_VERSION',     '1.0.0' );
define( 'PC_PLUGIN_FILE', __FILE__ );
define( 'PC_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'PC_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'PC_PLUGIN_SLUG', 'pylaris-contracts' );

// Autoloader mínimo para clases del plugin
spl_autoload_register( function ( $class ) {
    // Solo clases con prefijo PC_
    if ( strpos( $class, 'PC_' ) !== 0 ) {
        return;
    }

    $file = PC_PLUGIN_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Hooks de activación y desactivación
register_activation_hook( __FILE__, array( 'PC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PC_Deactivator', 'deactivate' ) );

// Arrancar el plugin
function pc_run() {
    $loader = new PC_Loader();
    $loader->run();
}
pc_run();
