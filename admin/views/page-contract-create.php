<?php
defined( 'ABSPATH' ) || exit;

require_once PC_PLUGIN_DIR . 'admin/class-pc-admin-contracts.php';

$send_link_result = null;

// ¿Estamos editando un contrato existente?
$editing     = isset( $_GET['edit'] ) && absint( $_GET['edit'] ) > 0;
$contract_id = $editing ? absint( $_GET['edit'] ) : 0;
$contract    = null;
$result      = null;

if ( $editing ) {
    $contract = PC_DB::get_contract_by_id( $contract_id );
    if ( ! $contract ) {
        wp_die( esc_html__( 'Contrato no encontrado.', 'pylaris-contracts' ) );
    }
    $send_link_result = PC_Admin_Contracts::handle_send_link();
    $result = PC_Admin_Contracts::handle_update( $contract_id );
    // Recargar tras actualización exitosa
    if ( $result && $result['success'] ) {
        $contract = PC_DB::get_contract_by_id( $contract_id );
    }
} else {
    $result = PC_Admin_Contracts::handle_create();
    // Si se creó OK, redirigir a la edición del nuevo contrato
    if ( $result && $result['success'] && $result['contract_id'] ) {
        wp_redirect( admin_url( 'admin.php?page=pylaris-contracts-new&edit=' . $result['contract_id'] . '&created=1' ) );
        exit;
    }
}

$input         = $result['input'] ?? array();
$status_labels = PC_Admin_Contracts::get_status_labels();
$nonce         = PC_Security::create_nonce( PC_Security::NONCE_ADMIN_CONTRACT );

// Helper para obtener valor: post fallback → contrato guardado → default
$val = function( $field, $default = '' ) use ( $input, $contract ) {
    if ( ! empty( $input ) && array_key_exists( $field, $input ) ) {
        return $input[ $field ];
    }
    if ( $contract && isset( $contract->$field ) ) {
        return $contract->$field;
    }
    return $default;
};

$is_signed   = $contract && $contract->status === 'signed';
$is_editable = ! $contract || PC_Helpers::is_editable_status( $contract->status );
?>
<div class="wrap pc-wrap">

    <h1>
        <?php if ( $editing ) : ?>
            <?php echo esc_html( sprintf( __( 'Contrato %s', 'pylaris-contracts' ), $contract->contract_number ?? '' ) ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=pylaris-contracts' ) ); ?>" class="page-title-action">
                ← <?php esc_html_e( 'Volver al listado', 'pylaris-contracts' ); ?>
            </a>
        <?php else : ?>
            <?php esc_html_e( 'Nuevo contrato', 'pylaris-contracts' ); ?>
        <?php endif; ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ( isset( $_GET['created'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Contrato creado correctamente.', 'pylaris-contracts' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $send_link_result ) ) : ?>
        <div class="notice notice-<?php echo $send_link_result['success'] ? 'success' : 'error'; ?> is-dismissible">
            <p><?php echo esc_html( $send_link_result['message'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $result && ! $result['success'] ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html( $result['message'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $result && $result['success'] && ! isset( $_GET['created'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $result['message'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( $is_signed ) : ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e( 'Este contrato está firmado.', 'pylaris-contracts' ); ?></strong>
                <?php esc_html_e( 'No puede ser modificado.', 'pylaris-contracts' ); ?>
                <?php if ( $contract->signed_at ) : ?>
                    <?php echo esc_html( sprintf(
                        __( 'Firmado el %s por %s.', 'pylaris-contracts' ),
                        PC_Helpers::format_date( $contract->signed_at ),
                        $contract->google_email_verified ?? '—'
                    ) ); ?>
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <div style="display:flex;gap:24px;align-items:flex-start;margin-top:16px;">

        <!-- Columna principal: formulario -->
        <div style="flex:1;min-width:0;">
            <form method="post" action="">
                <input type="hidden" name="pc_action" value="<?php echo $editing ? 'update_contract' : 'create_contract'; ?>">
                <input type="hidden" name="_wpnonce"  value="<?php echo esc_attr( $nonce ); ?>">
                <?php if ( $editing ) : ?>
                    <input type="hidden" name="contract_id" value="<?php echo esc_attr( $contract_id ); ?>">
                <?php endif; ?>

                <!-- DATOS DEL CLIENTE -->
                <div class="postbox">
                    <div class="postbox-header"><h2><?php esc_html_e( 'Datos del cliente', 'pylaris-contracts' ); ?></h2></div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="client_name"><?php esc_html_e( 'Nombre completo *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="client_name" name="client_name"
                                           value="<?php echo esc_attr( $val( 'client_name' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_email"><?php esc_html_e( 'Email *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="email" id="client_email" name="client_email"
                                           value="<?php echo esc_attr( $val( 'client_email' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                    <p class="description"><?php esc_html_e( 'Este email es el único que podrá acceder y firmar el contrato.', 'pylaris-contracts' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_dni_cuit"><?php esc_html_e( 'DNI / CUIT *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="client_dni_cuit" name="client_dni_cuit"
                                           value="<?php echo esc_attr( $val( 'client_dni_cuit' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="client_company"><?php esc_html_e( 'Empresa', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="client_company" name="client_company"
                                           value="<?php echo esc_attr( $val( 'client_company' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- DATOS DEL PROYECTO -->
                <div class="postbox">
                    <div class="postbox-header"><h2><?php esc_html_e( 'Datos del proyecto', 'pylaris-contracts' ); ?></h2></div>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><label for="project_title"><?php esc_html_e( 'Título del proyecto', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="project_title" name="project_title"
                                           value="<?php echo esc_attr( $val( 'project_title' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="project_scope"><?php esc_html_e( 'Alcance del proyecto *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <textarea id="project_scope" name="project_scope" rows="6" class="large-text"
                                              <?php echo ! $is_editable ? 'disabled' : ''; ?>><?php echo esc_textarea( $val( 'project_scope' ) ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="project_amount"><?php esc_html_e( 'Monto *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="number" id="project_amount" name="project_amount" step="0.01" min="0.01"
                                           value="<?php echo esc_attr( $val( 'project_amount' ) ); ?>"
                                           style="width:150px" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                    <select name="project_currency" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                        <?php foreach ( array( 'USD', 'ARS', 'EUR' ) as $currency ) : ?>
                                            <option value="<?php echo esc_attr( $currency ); ?>"
                                                <?php selected( $val( 'project_currency', 'USD' ), $currency ); ?>>
                                                <?php echo esc_html( $currency ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="delivery_time"><?php esc_html_e( 'Plazo de entrega *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="delivery_time" name="delivery_time"
                                           value="<?php echo esc_attr( $val( 'delivery_time' ) ); ?>"
                                           placeholder="Ej: 3 semanas" class="regular-text"
                                           <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="revision_rounds"><?php esc_html_e( 'Rondas de revisión *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="number" id="revision_rounds" name="revision_rounds" min="0" max="10"
                                           value="<?php echo esc_attr( $val( 'revision_rounds', 2 ) ); ?>"
                                           style="width:80px" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="jurisdiction"><?php esc_html_e( 'Jurisdicción *', 'pylaris-contracts' ); ?></label></th>
                                <td>
                                    <input type="text" id="jurisdiction" name="jurisdiction"
                                           value="<?php echo esc_attr( $val( 'jurisdiction', 'Buenos Aires, Argentina' ) ); ?>"
                                           class="regular-text" <?php echo ! $is_editable ? 'disabled' : ''; ?>>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header"><h2><?php esc_html_e( 'HTML del contrato *', 'pylaris-contracts' ); ?></h2></div>
                    <div class="inside">
                        <p class="description" style="margin-bottom:8px;">
                            <?php esc_html_e( 'Pegá el HTML final del contrato, o generalo desde el template base con los datos ingresados arriba.', 'pylaris-contracts' ); ?>
                        </p>
                        <?php if ( $is_editable ) : ?>
                            <p style="margin-bottom:10px;">
                                <button type="button" class="button button-primary" id="pc-btn-generate-template">
                                    ⚙ <?php esc_html_e( 'Generar desde template', 'pylaris-contracts' ); ?>
                                </button>
                                <span id="pc-template-status" style="margin-left:10px;font-size:13px;color:#888;"></span>
                            </p>
                        <?php endif; ?>
                        <textarea id="contract_html" name="contract_html" rows="20" class="large-text code"
                                  <?php echo ! $is_editable ? 'disabled' : ''; ?>
                                  style="font-family:monospace;font-size:12px;"><?php echo esc_textarea( $val( 'contract_html' ) ); ?></textarea>
                        <?php if ( $editing && $contract->contract_hash ) : ?>
                            <p class="description" style="margin-top:8px;">
                                <?php esc_html_e( 'Hash SHA-256 actual:', 'pylaris-contracts' ); ?>
                                <code style="font-size:11px;"><?php echo esc_html( $contract->contract_hash ); ?></code>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ( $is_editable ) : ?>
                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <?php echo $editing
                                ? esc_html__( 'Actualizar contrato', 'pylaris-contracts' )
                                : esc_html__( 'Crear contrato', 'pylaris-contracts' ); ?>
                        </button>
                    </p>
                <?php endif; ?>

            </form>
        </div>

        <!-- Columna lateral: info del contrato -->
        <?php if ( $editing && $contract ) : ?>
        <div style="width:280px;flex-shrink:0;">

            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e( 'Estado', 'pylaris-contracts' ); ?></h2></div>
                <div class="inside">
                    <p>
                        <strong><?php echo esc_html( $status_labels[ $contract->status ] ?? $contract->status ); ?></strong>
                    </p>
                    <p style="font-size:12px;color:#888;">
                        <?php esc_html_e( 'Creado:', 'pylaris-contracts' ); ?>
                        <?php echo esc_html( PC_Helpers::format_date( $contract->created_at ) ); ?>
                    </p>
                    <?php if ( $contract->expires_at ) : ?>
                        <p style="font-size:12px;color:#888;">
                            <?php esc_html_e( 'Vence:', 'pylaris-contracts' ); ?>
                            <?php echo esc_html( PC_Helpers::format_date( $contract->expires_at ) ); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Cambio de estado manual -->
                    <?php if ( $is_editable ) : ?>
                        <hr>
                        <p style="font-size:12px;"><strong><?php esc_html_e( 'Cambiar estado:', 'pylaris-contracts' ); ?></strong></p>
                        <?php if ( $contract->status === 'draft' ) : ?>
                            <form method="post" onsubmit="return confirm('<?php esc_attr_e( '¿Publicar para el cliente?', 'pylaris-contracts' ); ?>')">
                                <input type="hidden" name="pc_action"   value="change_status">
                                <input type="hidden" name="contract_id" value="<?php echo esc_attr( $contract->id ); ?>">
                                <input type="hidden" name="new_status"  value="pending">
                                <input type="hidden" name="_wpnonce"    value="<?php echo esc_attr( $nonce ); ?>">
                                <button type="submit" class="button button-primary" style="width:100%">
                                    <?php esc_html_e( 'Publicar (pending)', 'pylaris-contracts' ); ?>
                                </button>
                            </form>
                        <?php elseif ( $contract->status === 'pending' ) : ?>
                            <form method="post" onsubmit="return confirm('<?php esc_attr_e( '¿Cancelar?', 'pylaris-contracts' ); ?>')">
                                <input type="hidden" name="pc_action"   value="change_status">
                                <input type="hidden" name="contract_id" value="<?php echo esc_attr( $contract->id ); ?>">
                                <input type="hidden" name="new_status"  value="cancelled">
                                <input type="hidden" name="_wpnonce"    value="<?php echo esc_attr( $nonce ); ?>">
                                <button type="submit" class="button" style="width:100%">
                                    <?php esc_html_e( 'Cancelar contrato', 'pylaris-contracts' ); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Link público -->
            <?php if ( in_array( $contract->status, array( 'pending', 'signed' ), true ) ) : ?>
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e( 'Link del contrato', 'pylaris-contracts' ); ?></h2></div>
                <div class="inside">
                    <p style="font-size:12px;word-break:break-all;">
                        <?php
                        $contract_url = PC_Admin_Contracts::get_contract_url( $contract->token );
                        echo '<code>' . esc_html( $contract_url ) . '</code>';
                        ?>
                    </p>
                    <button class="button" style="width:100%;margin-bottom:6px;"
                            onclick="navigator.clipboard.writeText('<?php echo esc_js( $contract_url ); ?>');this.textContent='¡Copiado!'">
                        <?php esc_html_e( 'Copiar link', 'pylaris-contracts' ); ?>
                    </button>
                    <?php if ( 'pending' === $contract->status ) : ?>
                        <form method="post">
                            <input type="hidden" name="pc_action"   value="send_link">
                            <input type="hidden" name="contract_id" value="<?php echo esc_attr( $contract->id ); ?>">
                            <input type="hidden" name="_wpnonce"    value="<?php echo esc_attr( $nonce ); ?>">
                            <button type="submit" class="button button-primary" style="width:100%">
                                <?php esc_html_e( 'Enviar link al cliente', 'pylaris-contracts' ); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Datos de firma (si está firmado) -->
            <?php if ( $is_signed ) : ?>
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e( 'Datos de firma', 'pylaris-contracts' ); ?></h2></div>
                <div class="inside" style="font-size:13px;">
                    <p><strong><?php esc_html_e( 'Nombre:', 'pylaris-contracts' ); ?></strong><br><?php echo esc_html( $contract->signed_name ); ?></p>
                    <p><strong><?php esc_html_e( 'DNI/CUIT:', 'pylaris-contracts' ); ?></strong><br><?php echo esc_html( $contract->signed_dni_cuit ); ?></p>
                    <p><strong><?php esc_html_e( 'Email Google:', 'pylaris-contracts' ); ?></strong><br><?php echo esc_html( $contract->google_email_verified ); ?></p>
                    <p><strong><?php esc_html_e( 'Fecha y hora:', 'pylaris-contracts' ); ?></strong><br><?php echo esc_html( PC_Helpers::format_date( $contract->signed_at, 'd/m/Y H:i:s' ) ); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Vencimiento (editable para contratos no firmados) -->
            <?php if ( $is_editable ) : ?>
            <div class="postbox">
                <div class="postbox-header"><h2><?php esc_html_e( 'Vencimiento', 'pylaris-contracts' ); ?></h2></div>
                <div class="inside">
                    <p class="description" style="margin-bottom:8px;">
                        <?php esc_html_e( 'Opcional. Si se establece, el contrato expirará automáticamente.', 'pylaris-contracts' ); ?>
                    </p>
                    <form method="post">
                        <input type="hidden" name="pc_action"   value="<?php echo $editing ? 'update_contract' : 'create_contract'; ?>">
                        <input type="hidden" name="_wpnonce"    value="<?php echo esc_attr( $nonce ); ?>">
                        <input type="datetime-local" name="expires_at" class="regular-text"
                               value="<?php
                                   $expires = $val( 'expires_at' );
                                   echo $expires ? esc_attr( date( 'Y-m-d\TH:i', strtotime( $expires ) ) ) : '';
                               ?>">
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <?php endif; ?>

    </div><!-- columnas -->

</div><!-- .wrap -->
