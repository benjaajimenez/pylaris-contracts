<?php
defined( 'ABSPATH' ) || exit;

PC_Security::require_admin();

$saved = false;

if ( isset( $_POST['pc_settings_action'] ) && 'save' === $_POST['pc_settings_action'] ) {
    PC_Security::verify_nonce( $_POST['_wpnonce'] ?? '', 'pc_settings_save' );
    update_option( 'pc_google_client_id',   sanitize_text_field( $_POST['pc_google_client_id'] ?? '' ) );
    update_option( 'pc_mail_from_name',     sanitize_text_field( $_POST['pc_mail_from_name'] ?? '' ) );
    update_option( 'pc_mail_from_email',    sanitize_email( $_POST['pc_mail_from_email'] ?? '' ) );
    update_option( 'pc_mail_admin_email',   sanitize_email( $_POST['pc_mail_admin_email'] ?? '' ) );
    $saved = true;
}

$google_client_id = get_option( 'pc_google_client_id', '' );
$mail_from_name   = get_option( 'pc_mail_from_name', 'Pylaris Contracts' );
$mail_from_email  = get_option( 'pc_mail_from_email', get_option( 'admin_email' ) );
$mail_admin_email = get_option( 'pc_mail_admin_email', get_option( 'admin_email' ) );
?>
<div class="wrap pc-wrap">
    <h1><?php esc_html_e( 'Configuración — Pylaris Contracts', 'pylaris-contracts' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( $saved ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'Configuración guardada.', 'pylaris-contracts' ); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" style="max-width:640px;margin-top:24px;">
        <input type="hidden" name="pc_settings_action" value="save">
        <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( PC_Security::create_nonce( 'pc_settings_save' ) ); ?>">

        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e( 'Google Sign-In', 'pylaris-contracts' ); ?></h2></div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="pc_google_client_id"><?php esc_html_e( 'Google Client ID', 'pylaris-contracts' ); ?></label></th>
                        <td>
                            <input type="text" id="pc_google_client_id" name="pc_google_client_id"
                                   value="<?php echo esc_attr( $google_client_id ); ?>"
                                   class="large-text" placeholder="XXXXXXX.apps.googleusercontent.com">
                            <p class="description">
                                <?php esc_html_e( 'Obtenelo en Google Cloud Console → APIs & Services → Credentials → OAuth 2.0 Client ID.', 'pylaris-contracts' ); ?><br>
                                <?php esc_html_e( 'Agregá el dominio del sitio como Authorized JavaScript origin.', 'pylaris-contracts' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                <p><button type="submit" class="button button-primary"><?php esc_html_e( 'Guardar', 'pylaris-contracts' ); ?></button></p>
            </div>
        </div>

        <div class="postbox">
            <div class="postbox-header"><h2><?php esc_html_e( 'Emails transaccionales', 'pylaris-contracts' ); ?></h2></div>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><label for="pc_mail_from_name"><?php esc_html_e( 'Nombre del remitente', 'pylaris-contracts' ); ?></label></th>
                        <td>
                            <input type="text" id="pc_mail_from_name" name="pc_mail_from_name"
                                   value="<?php echo esc_attr( $mail_from_name ); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pc_mail_from_email"><?php esc_html_e( 'Email remitente (From)', 'pylaris-contracts' ); ?></label></th>
                        <td>
                            <input type="email" id="pc_mail_from_email" name="pc_mail_from_email"
                                   value="<?php echo esc_attr( $mail_from_email ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Debe coincidir con el dominio configurado en tu proveedor SMTP.', 'pylaris-contracts' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pc_mail_admin_email"><?php esc_html_e( 'Email interno Pylaris', 'pylaris-contracts' ); ?></label></th>
                        <td>
                            <input type="email" id="pc_mail_admin_email" name="pc_mail_admin_email"
                                   value="<?php echo esc_attr( $mail_admin_email ); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e( 'Recibe la notificación cuando un contrato es firmado.', 'pylaris-contracts' ); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="description" style="padding:8px 0;">
                    <?php esc_html_e( 'Para emails confiables en producción, configurá un plugin SMTP en WordPress (ej: WP Mail SMTP con tu cuenta de Brevo, SendGrid o similar).', 'pylaris-contracts' ); ?>
                </p>
            </div>
        </div>

        <p><button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Guardar configuración', 'pylaris-contracts' ); ?></button></p>
    </form>

    <div class="postbox" style="max-width:640px;margin-top:8px;">
        <div class="postbox-header"><h2><?php esc_html_e( 'Cómo configurar Google Sign-In', 'pylaris-contracts' ); ?></h2></div>
        <div class="inside" style="font-size:13px;line-height:1.8;">
            <ol>
                <li>Entrá a <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
                <li>Creá o seleccioná un proyecto</li>
                <li>Andá a <strong>APIs &amp; Services → Credentials</strong></li>
                <li>Clic en <strong>Create Credentials → OAuth client ID</strong></li>
                <li>Tipo: <strong>Web application</strong></li>
                <li>En <strong>Authorized JavaScript origins</strong> agregá: <code><?php echo esc_html( home_url() ); ?></code></li>
                <li>Copiá el <strong>Client ID</strong> y pegalo arriba</li>
            </ol>
        </div>
    </div>
</div>
