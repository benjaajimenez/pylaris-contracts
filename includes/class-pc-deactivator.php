<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Deactivator
 *
 * Se ejecuta al desactivar el plugin.
 * No elimina tablas ni datos — eso es responsabilidad de uninstall.php.
 * Solo limpia tareas programadas si las hubiera.
 */
class PC_Deactivator {

    public static function deactivate() {
        // Por ahora no hay cron jobs ni datos volátiles que limpiar.
        // Este método existe para mantener la estructura correcta
        // y poder agregar limpieza futura sin cambiar el hook.
    }
}
