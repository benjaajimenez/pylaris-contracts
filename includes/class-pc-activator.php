<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Activator
 *
 * Se ejecuta una sola vez al activar el plugin.
 * Responsabilidades:
 * - Crear las tablas de base de datos.
 * - Guardar la versión del plugin en options.
 */
class PC_Activator {

    /**
     * Punto de entrada del hook de activación.
     */
    public static function activate() {
        self::create_tables();
        self::set_version();

        // Registrar la regla de rewrite y hacer flush
        // para que /c/{token} funcione de inmediato.
        add_rewrite_rule(
            '^c/([a-zA-Z0-9]+)/?$',
            'index.php?pc_contract_token=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^c/([a-zA-Z0-9]+)/constancia/?$',
            'index.php?pc_contract_token=$matches[1]&pc_action=constancia',
            'top'
        );
        flush_rewrite_rules();
    }

    /**
     * Crea las tres tablas del sistema si no existen.
     * Usa dbDelta para compatibilidad con actualizaciones futuras.
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // --------------------------------------------------------
        // Tabla: pc_contracts
        // --------------------------------------------------------
        $table_contracts = $wpdb->prefix . 'pc_contracts';

        $sql_contracts = "CREATE TABLE {$table_contracts} (
            id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_number     VARCHAR(50)     NOT NULL,
            token               VARCHAR(100)    NOT NULL,
            client_name         VARCHAR(255)    NOT NULL,
            client_email        VARCHAR(255)    NOT NULL,
            client_dni_cuit     VARCHAR(50)     NOT NULL,
            client_company      VARCHAR(255)    DEFAULT NULL,
            project_title       VARCHAR(255)    DEFAULT NULL,
            project_scope       LONGTEXT        NOT NULL,
            project_amount      DECIMAL(12,2)   NOT NULL,
            project_currency    VARCHAR(10)     NOT NULL,
            delivery_time       VARCHAR(100)    NOT NULL,
            revision_rounds     TINYINT UNSIGNED NOT NULL DEFAULT 2,
            jurisdiction        VARCHAR(255)    NOT NULL,
            status              VARCHAR(20)     NOT NULL DEFAULT 'draft',
            contract_html       LONGTEXT        NOT NULL,
            contract_hash       CHAR(64)        NOT NULL,
            google_email_verified VARCHAR(255)  DEFAULT NULL,
            signed_name         VARCHAR(255)    DEFAULT NULL,
            signed_dni_cuit     VARCHAR(50)     DEFAULT NULL,
            signed_at           DATETIME        DEFAULT NULL,
            expires_at          DATETIME        DEFAULT NULL,
            created_at          DATETIME        NOT NULL,
            updated_at          DATETIME        NOT NULL,
            PRIMARY KEY         (id),
            UNIQUE KEY          uniq_contract_number (contract_number),
            UNIQUE KEY          uniq_token (token),
            KEY                 idx_client_email (client_email),
            KEY                 idx_status (status),
            KEY                 idx_expires_at (expires_at),
            KEY                 idx_created_at (created_at)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_contracts );

        // --------------------------------------------------------
        // Tabla: pc_signatures
        // --------------------------------------------------------
        $table_signatures = $wpdb->prefix . 'pc_signatures';

        $sql_signatures = "CREATE TABLE {$table_signatures} (
            id                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_id              BIGINT UNSIGNED NOT NULL,
            google_sub               VARCHAR(255)    DEFAULT NULL,
            google_email             VARCHAR(255)    NOT NULL,
            google_name              VARCHAR(255)    DEFAULT NULL,
            ip_address               VARCHAR(100)    DEFAULT NULL,
            user_agent               TEXT            DEFAULT NULL,
            accepted_checkbox        TINYINT(1)      NOT NULL DEFAULT 0,
            signed_name              VARCHAR(255)    NOT NULL,
            signed_dni_cuit          VARCHAR(50)     NOT NULL,
            contract_hash_at_signature CHAR(64)      NOT NULL,
            created_at               DATETIME        NOT NULL,
            PRIMARY KEY              (id),
            KEY                      idx_contract_id (contract_id),
            KEY                      idx_google_email (google_email),
            KEY                      idx_created_at (created_at)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_signatures );

        // --------------------------------------------------------
        // Tabla: pc_contract_logs
        // --------------------------------------------------------
        $table_logs = $wpdb->prefix . 'pc_contract_logs';

        $sql_logs = "CREATE TABLE {$table_logs} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contract_id BIGINT UNSIGNED NOT NULL,
            event_type  VARCHAR(100)    NOT NULL,
            event_data  LONGTEXT        DEFAULT NULL,
            created_at  DATETIME        NOT NULL,
            PRIMARY KEY (id),
            KEY         idx_contract_id (contract_id),
            KEY         idx_event_type (event_type),
            KEY         idx_created_at (created_at)
        ) ENGINE=InnoDB {$charset_collate};";

        dbDelta( $sql_logs );
    }

    /**
     * Guarda la versión actual del plugin en wp_options.
     * Útil para detectar necesidad de migraciones futuras.
     */
    private static function set_version() {
        update_option( 'pc_version', PC_VERSION );
    }
}
