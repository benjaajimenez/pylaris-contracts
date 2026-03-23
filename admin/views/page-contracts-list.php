<?php
defined( 'ABSPATH' ) || exit;

require_once PC_PLUGIN_DIR . 'admin/class-pc-admin-contracts.php';

// Procesar cambio de estado si viene por POST
$status_result = PC_Admin_Contracts::handle_status_change();

// Preparar datos del listado
$list_data      = PC_Admin_Contracts::prepare_list_data();
$contracts      = $list_data['items'];
$total          = $list_data['total'];
$total_pages    = $list_data['pages'];
$current_page   = max( 1, absint( $_GET['paged'] ?? 1 ) );
$current_status = sanitize_text_field( $_GET['status'] ?? '' );
$current_search = sanitize_text_field( $_GET['s'] ?? '' );
$status_labels  = PC_Admin_Contracts::get_status_labels();

$status_classes = array(
    'draft'     => 'pc-badge--draft',
    'pending'   => 'pc-badge--pending',
    'signed'    => 'pc-badge--signed',
    'expired'   => 'pc-badge--expired',
    'cancelled' => 'pc-badge--cancelled',
);

$nonce = PC_Security::create_nonce( PC_Security::NONCE_ADMIN_CONTRACT );
?>
<div class="wrap pc-wrap">

    <h1 class="wp-heading-inline"><?php esc_html_e( 'Contratos', 'pylaris-contracts' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts-new' ) ); ?>" class="page-title-action">
        + <?php esc_html_e( 'Nuevo contrato', 'pylaris-contracts' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( $status_result ) : ?>
        <div class="notice notice-<?php echo $status_result['success'] ? 'success' : 'error'; ?> is-dismissible">
            <p><?php echo esc_html( $status_result['message'] ); ?></p>
        </div>
    <?php endif; ?>

    <!-- Filtros por estado -->
    <ul class="subsubsub">
        <li>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts' ) ); ?>"
               class="<?php echo '' === $current_status ? 'current' : ''; ?>">
                <?php esc_html_e( 'Todos', 'pylaris-contracts' ); ?>
            </a>
        </li>
        <?php foreach ( $status_labels as $slug => $label ) : ?>
            <li> |
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts&status=' . $slug ) ); ?>"
                   class="<?php echo $current_status === $slug ? 'current' : ''; ?>">
                    <?php echo esc_html( $label ); ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Búsqueda -->
    <form method="get" action="">
        <input type="hidden" name="page" value="pylaris-contracts">
        <?php if ( $current_status ) : ?>
            <input type="hidden" name="status" value="<?php echo esc_attr( $current_status ); ?>">
        <?php endif; ?>
        <p class="search-box">
            <input type="search" name="s" value="<?php echo esc_attr( $current_search ); ?>"
                   placeholder="<?php esc_attr_e( 'Buscar por cliente, email o número...', 'pylaris-contracts' ); ?>"
                   style="width:280px;">
            <button type="submit" class="button"><?php esc_html_e( 'Buscar', 'pylaris-contracts' ); ?></button>
        </p>
    </form>

    <!-- Tabla -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:130px"><?php esc_html_e( 'N° Contrato', 'pylaris-contracts' ); ?></th>
                <th><?php esc_html_e( 'Cliente', 'pylaris-contracts' ); ?></th>
                <th><?php esc_html_e( 'Email', 'pylaris-contracts' ); ?></th>
                <th style="width:110px"><?php esc_html_e( 'Monto', 'pylaris-contracts' ); ?></th>
                <th style="width:100px"><?php esc_html_e( 'Estado', 'pylaris-contracts' ); ?></th>
                <th style="width:130px"><?php esc_html_e( 'Creado', 'pylaris-contracts' ); ?></th>
                <th style="width:200px"><?php esc_html_e( 'Acciones', 'pylaris-contracts' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $contracts ) ) : ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:32px;color:#888;">
                        <?php esc_html_e( 'No hay contratos.', 'pylaris-contracts' ); ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ( $contracts as $contract ) : ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts-new&edit=' . $contract->id ) ); ?>">
                                    <?php echo esc_html( $contract->contract_number ); ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <?php echo esc_html( $contract->client_name ); ?>
                            <?php if ( $contract->client_company ) : ?>
                                <br><small style="color:#888"><?php echo esc_html( $contract->client_company ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $contract->client_email ); ?></td>
                        <td>
                            <?php echo esc_html( number_format( (float) $contract->project_amount, 2 ) ); ?>
                            <small style="color:#888"><?php echo esc_html( $contract->project_currency ); ?></small>
                        </td>
                        <td>
                            <span class="pc-badge <?php echo esc_attr( $status_classes[ $contract->status ] ?? '' ); ?>">
                                <?php echo esc_html( $status_labels[ $contract->status ] ?? $contract->status ); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html( PC_Helpers::format_date( $contract->created_at ) ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts-new&edit=' . $contract->id ) ); ?>"
                               class="button button-small">
                                <?php esc_html_e( 'Ver / Editar', 'pylaris-contracts' ); ?>
                            </a>

                            <?php if ( 'draft' === $contract->status ) : ?>
                                <form method="post" style="display:inline"
                                      onsubmit="return confirm('<?php esc_attr_e( '¿Publicar este contrato para el cliente?', 'pylaris-contracts' ); ?>')">
                                    <input type="hidden" name="pc_action"    value="change_status">
                                    <input type="hidden" name="contract_id"  value="<?php echo esc_attr( $contract->id ); ?>">
                                    <input type="hidden" name="new_status"   value="pending">
                                    <input type="hidden" name="_wpnonce"     value="<?php echo esc_attr( $nonce ); ?>">
                                    <button type="submit" class="button button-small button-primary">
                                        <?php esc_html_e( 'Publicar', 'pylaris-contracts' ); ?>
                                    </button>
                                </form>
                            <?php elseif ( 'pending' === $contract->status ) : ?>
                                <form method="post" style="display:inline"
                                      onsubmit="return confirm('<?php esc_attr_e( '¿Cancelar este contrato? No podrá reactivarse.', 'pylaris-contracts' ); ?>')">
                                    <input type="hidden" name="pc_action"    value="change_status">
                                    <input type="hidden" name="contract_id"  value="<?php echo esc_attr( $contract->id ); ?>">
                                    <input type="hidden" name="new_status"   value="cancelled">
                                    <input type="hidden" name="_wpnonce"     value="<?php echo esc_attr( $nonce ); ?>">
                                    <button type="submit" class="button button-small">
                                        <?php esc_html_e( 'Cancelar', 'pylaris-contracts' ); ?>
                                    </button>
                                </form>
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
                <span class="displaying-num">
                    <?php echo esc_html( number_format_i18n( $total ) . ' ' . _n( 'contrato', 'contratos', $total, 'pylaris-contracts' ) ); ?>
                </span>
                &nbsp;
                <?php
                $base_url = admin_url( 'admin.php?page=pylaris-contracts' );
                if ( $current_status ) $base_url .= '&status=' . urlencode( $current_status );
                if ( $current_search ) $base_url .= '&s=' . urlencode( $current_search );

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
