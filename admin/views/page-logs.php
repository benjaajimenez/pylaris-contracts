<?php
defined( 'ABSPATH' ) || exit;

PC_Security::require_admin();

global $wpdb;

$logs_table      = PC_DB::logs_table();
$contracts_table = PC_DB::contracts_table();

// Filtros
$filter_contract = absint( $_GET['contract_id'] ?? 0 );
$filter_event    = sanitize_text_field( $_GET['event_type'] ?? '' );
$per_page        = 50;
$current_page    = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset          = ( $current_page - 1 ) * $per_page;

// Construir query
$where  = array( '1=1' );
$values = array();

if ( $filter_contract ) {
    $where[]  = 'l.contract_id = %d';
    $values[] = $filter_contract;
}

if ( $filter_event ) {
    $where[]  = 'l.event_type = %s';
    $values[] = $filter_event;
}

$where_sql = implode( ' AND ', $where );

$count_sql = "SELECT COUNT(*) FROM `{$logs_table}` l WHERE {$where_sql}";
if ( $values ) {
    $count_sql = $wpdb->prepare( $count_sql, $values ); // phpcs:ignore
}
$total       = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore
$total_pages = $total > 0 ? (int) ceil( $total / $per_page ) : 1;

$query_sql = "SELECT l.*, c.contract_number, c.client_name
              FROM `{$logs_table}` l
              LEFT JOIN `{$contracts_table}` c ON c.id = l.contract_id
              WHERE {$where_sql}
              ORDER BY l.created_at DESC
              LIMIT %d OFFSET %d";

$query_values = array_merge( $values, array( $per_page, $offset ) );
$logs = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_values ) ); // phpcs:ignore

// Etiquetas y colores por tipo de evento
$event_labels = array(
    'contract_created'         => array( 'label' => 'Creado',             'color' => '#888' ),
    'contract_updated'         => array( 'label' => 'Actualizado',        'color' => '#856404' ),
    'contract_status_changed'  => array( 'label' => 'Estado cambiado',    'color' => '#0d6efd' ),
    'contract_signed'          => array( 'label' => 'Firmado',            'color' => '#155724' ),
    'contract_expired'         => array( 'label' => 'Vencido',            'color' => '#842029' ),
    'contract_email_sent'      => array( 'label' => 'Email enviado',      'color' => '#0a5a82' ),
    'google_login_success'     => array( 'label' => 'Login OK',           'color' => '#155724' ),
    'google_login_failed'      => array( 'label' => 'Login fallido',      'color' => '#842029' ),
    'contract_viewed'          => array( 'label' => 'Visualizado',        'color' => '#555' ),
);

// Lista de tipos de evento para el filtro
$event_types = $wpdb->get_col( "SELECT DISTINCT event_type FROM `{$logs_table}` ORDER BY event_type" ); // phpcs:ignore

// Contrato seleccionado para el filtro
$selected_contract = null;
if ( $filter_contract ) {
    $selected_contract = PC_DB::get_contract_by_id( $filter_contract );
}
?>
<div class="wrap pc-wrap">

    <h1><?php esc_html_e( 'Logs del sistema', 'pylaris-contracts' ); ?></h1>
    <hr class="wp-header-end">

    <!-- Filtros -->
    <form method="get" action="" style="margin:16px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <input type="hidden" name="page" value="pylaris-contracts-logs">

        <select name="event_type">
            <option value=""><?php esc_html_e( 'Todos los eventos', 'pylaris-contracts' ); ?></option>
            <?php foreach ( $event_types as $type ) : ?>
                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $filter_event, $type ); ?>>
                    <?php echo esc_html( $event_labels[ $type ]['label'] ?? $type ); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="number" name="contract_id" placeholder="ID de contrato"
               value="<?php echo esc_attr( $filter_contract ?: '' ); ?>"
               style="width:140px;">

        <button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'pylaris-contracts' ); ?></button>

        <?php if ( $filter_contract || $filter_event ) : ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts-logs' ) ); ?>" class="button">
                <?php esc_html_e( 'Limpiar filtros', 'pylaris-contracts' ); ?>
            </a>
        <?php endif; ?>

        <span style="margin-left:auto;color:#888;font-size:13px;">
            <?php echo esc_html( number_format_i18n( $total ) ); ?> <?php esc_html_e( 'registros', 'pylaris-contracts' ); ?>
        </span>
    </form>

    <?php if ( $selected_contract ) : ?>
        <div style="background:#f0f6ff;border-left:3px solid #0d6efd;padding:8px 16px;margin-bottom:16px;font-size:13px;">
            <?php echo esc_html( sprintf(
                'Mostrando logs del contrato: %s — %s',
                $selected_contract->contract_number,
                $selected_contract->client_name
            ) ); ?>
        </div>
    <?php endif; ?>

    <!-- Tabla de logs -->
    <table class="wp-list-table widefat fixed striped" style="font-size:13px;">
        <thead>
            <tr>
                <th style="width:50px">ID</th>
                <th style="width:160px"><?php esc_html_e( 'Fecha (UTC)', 'pylaris-contracts' ); ?></th>
                <th style="width:120px"><?php esc_html_e( 'Contrato', 'pylaris-contracts' ); ?></th>
                <th style="width:140px"><?php esc_html_e( 'Evento', 'pylaris-contracts' ); ?></th>
                <th><?php esc_html_e( 'Datos', 'pylaris-contracts' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $logs ) ) : ?>
                <tr>
                    <td colspan="5" style="text-align:center;padding:32px;color:#888;">
                        <?php esc_html_e( 'No hay logs que coincidan.', 'pylaris-contracts' ); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $logs as $log ) : ?>
                    <?php
                    $event_meta  = $event_labels[ $log->event_type ] ?? array( 'label' => $log->event_type, 'color' => '#888' );
                    $event_data  = $log->event_data ? json_decode( $log->event_data, true ) : null;
                    ?>
                    <tr>
                        <td style="color:#aaa;"><?php echo esc_html( $log->id ); ?></td>
                        <td style="color:#555;font-size:12px;">
                            <?php echo esc_html( PC_Helpers::format_date( $log->created_at, 'd/m/Y H:i:s' ) ); ?>
                        </td>
                        <td>
                            <?php if ( $log->contract_number ) : ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts-new&edit=' . $log->contract_id ) ); ?>"
                                   style="font-size:12px;">
                                    <?php echo esc_html( $log->contract_number ); ?>
                                </a>
                                <?php if ( $log->client_name ) : ?>
                                    <br><small style="color:#aaa;"><?php echo esc_html( $log->client_name ); ?></small>
                                <?php endif; ?>
                            <?php else : ?>
                                <span style="color:#aaa;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="
                                display:inline-block;
                                padding:2px 8px;
                                border-radius:20px;
                                font-size:11px;
                                font-weight:600;
                                background:<?php echo esc_attr( $event_meta['color'] ); ?>1a;
                                color:<?php echo esc_attr( $event_meta['color'] ); ?>;
                            ">
                                <?php echo esc_html( $event_meta['label'] ); ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:#555;">
                            <?php if ( $event_data ) : ?>
                                <?php foreach ( $event_data as $key => $val ) : ?>
                                    <?php if ( is_scalar( $val ) ) : ?>
                                        <span style="display:inline-block;margin-right:12px;">
                                            <strong><?php echo esc_html( $key ); ?>:</strong>
                                            <?php echo esc_html( $val ); ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <span style="color:#ccc;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $base_url = admin_url( 'admin.php?page=pylaris-contracts-logs' );
                if ( $filter_event )    $base_url .= '&event_type=' . urlencode( $filter_event );
                if ( $filter_contract ) $base_url .= '&contract_id=' . $filter_contract;

                for ( $i = 1; $i <= $total_pages; $i++ ) :
                    if ( $i === $current_page ) :
                        echo '<span class="current">' . esc_html( $i ) . '</span> ';
                    else :
                        echo '<a href="' . esc_url( $base_url . '&paged=' . $i ) . '">' . esc_html( $i ) . '</a> ';
                    endif;
                endfor;
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>
