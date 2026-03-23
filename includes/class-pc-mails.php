<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Mails
 *
 * Responsabilidades:
 * - Enviar el link del contrato al cliente.
 * - Enviar constancia de firma al cliente.
 * - Enviar notificación interna a Pylaris.
 *
 * Todos los emails usan wp_mail() con HTML.
 * El From se configura en wp_options (pc_mail_from_name, pc_mail_from_email).
 * Si no están configurados, usa los valores del sitio WordPress.
 */
class PC_Mails {

    // ----------------------------------------------------------------
    // Emails públicos
    // ----------------------------------------------------------------

    /**
     * Envía el link del contrato al cliente.
     * Se llama cuando el contrato pasa a estado 'pending'.
     *
     * @param  object $contract
     * @return bool
     */
    public static function send_contract_link( $contract ) {
        $to      = $contract->client_email;
        $subject = sprintf( 'Tu contrato con Pylaris — %s', $contract->contract_number );

        $contract_url = home_url( '/c/' . $contract->token );

        $body = self::wrap_template(
            $contract,
            'Recibiste un contrato para revisar y firmar',
            sprintf(
                '<p>Hola <strong>%s</strong>,</p>
                <p>Pylaris te envió un contrato para que lo revises y aceptes.</p>
                <p>Hacé clic en el botón para acceder:</p>',
                esc_html( $contract->client_name )
            ),
            array(
                'label' => 'Ver y firmar contrato',
                'url'   => $contract_url,
            ),
            sprintf(
                '<p style="font-size:13px;color:#888;margin-top:24px;">
                    Si el botón no funciona, copiá este enlace en tu navegador:<br>
                    <a href="%s" style="color:#FF6B35;word-break:break-all;">%s</a>
                </p>
                <p style="font-size:12px;color:#aaa;">
                    Este link es privado y está asignado a tu cuenta de Google (%s).
                    Solo vos podés acceder a este documento.
                </p>',
                esc_url( $contract_url ),
                esc_html( $contract_url ),
                esc_html( $contract->client_email )
            )
        );

        $sent = self::send( $to, $subject, $body );

        PC_DB::log_event( $contract->id, 'contract_email_sent', array(
            'type'    => 'contract_link',
            'to'      => $to,
            'success' => $sent,
        ) );

        return $sent;
    }

    /**
     * Envía la constancia de firma al cliente.
     * Se llama inmediatamente después de que el contrato queda firmado.
     *
     * @param  object $contract  Objeto del contrato ya firmado.
     * @return bool
     */
    public static function send_signature_confirmation( $contract ) {
        $to      = $contract->client_email;
        $subject = sprintf( 'Confirmación de firma — Contrato %s', $contract->contract_number );

        $body = self::wrap_template(
            $contract,
            'Tu firma fue registrada correctamente',
            sprintf(
                '<p>Hola <strong>%s</strong>,</p>
                <p>Tu aceptación del contrato <strong>%s</strong> fue registrada correctamente.</p>',
                esc_html( $contract->client_name ),
                esc_html( $contract->contract_number )
            ),
            null,
            self::build_signature_detail_table( $contract )
        );

        $sent = self::send( $to, $subject, $body );

        PC_DB::log_event( $contract->id, 'contract_email_sent', array(
            'type'    => 'signature_confirmation',
            'to'      => $to,
            'success' => $sent,
        ) );

        return $sent;
    }

    /**
     * Envía notificación interna a Pylaris cuando un contrato es firmado.
     *
     * @param  object $contract
     * @return bool
     */
    public static function send_internal_notification( $contract ) {
        $admin_email = self::get_admin_email();

        if ( empty( $admin_email ) ) {
            return false;
        }

        $to      = $admin_email;
        $subject = sprintf( '[Pylaris Contracts] Contrato firmado — %s', $contract->contract_number );

        $body = self::wrap_template(
            $contract,
            sprintf( 'Contrato firmado: %s', $contract->contract_number ),
            sprintf(
                '<p>El contrato <strong>%s</strong> fue aceptado y firmado.</p>',
                esc_html( $contract->contract_number )
            ),
            null,
            self::build_signature_detail_table( $contract )
        );

        $sent = self::send( $to, $subject, $body );

        PC_DB::log_event( $contract->id, 'contract_email_sent', array(
            'type'    => 'internal_notification',
            'to'      => $to,
            'success' => $sent,
        ) );

        return $sent;
    }

    // ----------------------------------------------------------------
    // Core: envío con headers HTML
    // ----------------------------------------------------------------

    /**
     * Envía un email HTML usando wp_mail().
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $body     HTML completo del email.
     * @return bool
     */
    private static function send( $to, $subject, $body ) {
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', self::get_from_name(), self::get_from_email() ),
        );

        return wp_mail( $to, $subject, $body, $headers );
    }

    // ----------------------------------------------------------------
    // Template del email
    // ----------------------------------------------------------------

    /**
     * Envuelve el contenido en el template HTML del email.
     *
     * @param  object      $contract
     * @param  string      $headline   Título principal del email.
     * @param  string      $intro_html HTML del párrafo introductorio.
     * @param  array|null  $cta        { label, url } o null.
     * @param  string      $extra_html HTML adicional al pie del contenido.
     * @return string
     */
    private static function wrap_template( $contract, $headline, $intro_html, $cta = null, $extra_html = '' ) {
        $cta_html = '';
        if ( $cta ) {
            $cta_html = sprintf(
                '<p style="text-align:center;margin:32px 0;">
                    <a href="%s"
                       style="background:#FF6B35;color:#fff;padding:14px 32px;border-radius:6px;
                              text-decoration:none;font-weight:500;font-size:15px;display:inline-block;">
                        %s
                    </a>
                </p>',
                esc_url( $cta['url'] ),
                esc_html( $cta['label'] )
            );
        }

        return sprintf(
            '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>%s</title>
</head>
<body style="margin:0;padding:0;background:#F2EFE9;font-family:\'Helvetica Neue\',Arial,sans-serif;">
<table width="100%%" cellpadding="0" cellspacing="0" border="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;width:100%%;">

  <!-- Header -->
  <tr>
    <td style="background:#0F0F0F;padding:24px 40px;border-radius:6px 6px 0 0;">
      <span style="font-family:Georgia,serif;font-size:22px;font-weight:700;color:#fff;letter-spacing:-.5px;">
        Pyla<span style="color:#FF6B35;">ris</span>
      </span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#fff;padding:40px;border-left:1px solid #E0DDD8;border-right:1px solid #E0DDD8;">
      <h1 style="font-family:Georgia,serif;font-size:22px;font-weight:700;color:#0F0F0F;margin:0 0 24px;line-height:1.3;">
        %s
      </h1>
      <div style="font-size:15px;color:#3A3A3A;line-height:1.7;">
        %s
      </div>
      %s
      <div style="font-size:14px;color:#3A3A3A;line-height:1.7;">
        %s
      </div>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#F7F5F2;padding:20px 40px;border:1px solid #E0DDD8;border-top:none;border-radius:0 0 6px 6px;
               font-size:12px;color:#999;text-align:center;line-height:1.6;">
      Pylaris — Agencia de Marketing Digital<br>
      <a href="%s" style="color:#FF6B35;text-decoration:none;">pylaris.com</a>
      · Este mensaje es confidencial y está dirigido exclusivamente a su destinatario.
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>',
            esc_html( $headline ),
            esc_html( $headline ),
            $intro_html,
            $cta_html,
            $extra_html,
            esc_url( home_url() )
        );
    }

    /**
     * Genera la tabla HTML con los detalles de la firma.
     *
     * @param  object $contract
     * @return string
     */
    private static function build_signature_detail_table( $contract ) {
        $rows = array(
            'N° de contrato'  => esc_html( $contract->contract_number ),
            'Fecha de firma'  => esc_html( PC_Helpers::format_date( $contract->signed_at, 'd/m/Y H:i' ) ),
            'Firmado por'     => esc_html( $contract->signed_name ?? '—' ),
            'DNI / CUIT'      => esc_html( $contract->signed_dni_cuit ?? '—' ),
            'Email verificado' => esc_html( $contract->google_email_verified ?? '—' ),
        );

        $rows_html = '';
        foreach ( $rows as $label => $value ) {
            $rows_html .= sprintf(
                '<tr>
                    <td style="padding:10px 16px;font-size:12px;font-weight:600;color:#888;
                               text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #E0DDD8;
                               width:40%%;background:#FAFAF8;">%s</td>
                    <td style="padding:10px 16px;font-size:14px;font-weight:500;color:#1A1A1A;
                               border-bottom:1px solid #E0DDD8;">%s</td>
                </tr>',
                esc_html( $label ),
                $value
            );
        }

        return sprintf(
            '<table width="100%%" cellpadding="0" cellspacing="0" border="0"
                    style="border:1.5px solid #E0DDD8;border-radius:6px;overflow:hidden;
                           margin:24px 0;font-family:\'Helvetica Neue\',Arial,sans-serif;">
                %s
            </table>',
            $rows_html
        );
    }

    // ----------------------------------------------------------------
    // Configuración
    // ----------------------------------------------------------------

    private static function get_from_name() {
        return get_option( 'pc_mail_from_name', 'Pylaris Contracts' );
    }

    private static function get_from_email() {
        $default = get_option( 'admin_email', '' );
        return get_option( 'pc_mail_from_email', $default );
    }

    private static function get_admin_email() {
        return get_option( 'pc_mail_admin_email', get_option( 'admin_email', '' ) );
    }
}
