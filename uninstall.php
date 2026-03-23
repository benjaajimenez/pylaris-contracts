<?php
/**
 * Pylaris Contracts — Uninstall
 *
 * Se ejecuta cuando el administrador elimina el plugin desde el panel de WP.
 * Elimina todas las tablas y opciones del plugin de la base de datos.
 *
 * ATENCIÓN: Esta acción es irreversible.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$tables = array(
    $wpdb->prefix . 'pc_contract_logs',
    $wpdb->prefix . 'pc_signatures',
    $wpdb->prefix . 'pc_contracts',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore
}

delete_option( 'pc_version' );
