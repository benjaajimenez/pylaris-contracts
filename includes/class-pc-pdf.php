<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_PDF
 *
 * Genera la constancia de firma del contrato.
 *
 * Estrategia:
 * - Si está disponible MPDF en vendor/, lo usa para generar un PDF real.
 * - Si no, genera un HTML descargable bien formateado (fallback).
 *
 * Para instalar MPDF:
 *   cd wp-content/plugins/pylaris-contracts
 *   composer require mpdf/mpdf
 *
 * La constancia incluye:
 * - Datos del contrato
 * - Datos del firmante
 * - Hash del documento
 * - Metadatos de evidencia
 * - El contenido del contrato firmado
 */
class PC_PDF {

    /**
     * Genera la constancia y la sirve como descarga al navegador.
     * Termina la ejecución de PHP al finalizar.
     *
     * @param object $contract  Contrato en estado 'signed'.
     */
    public static function download( $contract ) {
        if ( $contract->status !== 'signed' ) {
            wp_die( esc_html__( 'Solo se puede generar constancia de contratos firmados.', 'pylaris-contracts' ) );
        }

        if ( self::mpdf_available() ) {
            self::download_with_mpdf( $contract );
        } else {
            self::download_as_html( $contract );
        }
    }

    /**
     * Genera el contenido HTML de la constancia (reutilizable para email y PDF).
     *
     * @param  object $contract
     * @return string
     */
    public static function build_html( $contract ) {
        $signed_at_formatted = PC_Helpers::format_date( $contract->signed_at, 'd/m/Y \a \l\a\s H:i' );

        ob_start();
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Constancia de firma — <?php echo esc_html( $contract->contract_number ); ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap');

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'DM Sans', 'Helvetica Neue', Arial, sans-serif;
    background: #fff;
    color: #1A1A1A;
    font-size: 14px;
    line-height: 1.7;
  }

  /* Portada de constancia */
  .cert-cover {
    max-width: 800px;
    margin: 40px auto;
    padding: 0 32px;
  }

  .cert-header {
    background: #0F0F0F;
    padding: 32px 40px;
    border-radius: 6px 6px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
  }

  .cert-brand {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 24px;
    font-weight: 700;
    color: #fff;
    letter-spacing: -.5px;
  }

  .cert-brand span { color: #FF6B35; }

  .cert-contract-ref {
    font-size: 12px;
    color: rgba(255,255,255,.4);
    text-align: right;
    line-height: 1.8;
  }

  .cert-body {
    border: 1.5px solid #E0DDD8;
    border-top: none;
    padding: 40px;
    border-radius: 0 0 6px 6px;
    background: #fff;
  }

  .cert-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 22px;
    font-weight: 700;
    color: #0F0F0F;
    margin-bottom: 8px;
  }

  .cert-subtitle {
    font-size: 13px;
    color: #888;
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #E0DDD8;
  }

  /* Tabla de datos */
  .cert-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 32px;
  }

  .cert-table tr td {
    padding: 10px 16px;
    border-bottom: 1px solid #E0DDD8;
    vertical-align: top;
  }

  .cert-table tr td:first-child {
    width: 38%;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #888;
    background: #FAFAF8;
  }

  .cert-table tr td:last-child {
    font-weight: 500;
    color: #1A1A1A;
  }

  /* Hash block */
  .cert-hash {
    background: #F7F5F2;
    border: 1.5px solid #E0DDD8;
    border-radius: 6px;
    padding: 16px 20px;
    margin-bottom: 32px;
  }

  .cert-hash__label {
    font-size: 11px;
    font-weight: 500;
    letter-spacing: .8px;
    text-transform: uppercase;
    color: #888;
    margin-bottom: 8px;
  }

  .cert-hash__value {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    color: #333;
    word-break: break-all;
    line-height: 1.5;
  }

  /* Nota legal */
  .cert-legal {
    font-size: 12px;
    color: #aaa;
    line-height: 1.6;
    padding-top: 16px;
    border-top: 1px solid #E0DDD8;
  }

  /* Divisor entre constancia y contrato */
  .cert-divider {
    max-width: 800px;
    margin: 0 auto;
    padding: 24px 32px;
    text-align: center;
    font-size: 12px;
    color: #aaa;
    border-top: 2px dashed #E0DDD8;
    border-bottom: 2px dashed #E0DDD8;
    background: #FAFAF8;
  }

  /* Contenido del contrato */
  .cert-contract-content {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 32px 40px;
  }

  @media print {
    .cert-cover { margin: 0; }
    .cert-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .cert-hash { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>

<div class="cert-cover">
  <!-- Header -->
  <div class="cert-header">
    <div class="cert-brand">Pyla<span>ris</span></div>
    <div class="cert-contract-ref">
      Constancia N° <?php echo esc_html( $contract->contract_number ); ?><br>
      Emitida: <?php echo esc_html( PC_Helpers::format_date( current_time( 'mysql', true ), 'd/m/Y H:i' ) ); ?>
    </div>
  </div>

  <!-- Body -->
  <div class="cert-body">
    <h1 class="cert-title">Constancia de aceptación</h1>
    <p class="cert-subtitle">
      Documento que certifica la aceptación electrónica del contrato por parte del firmante.
    </p>

    <!-- Datos del contrato -->
    <table class="cert-table">
      <tr>
        <td>N° de contrato</td>
        <td><?php echo esc_html( $contract->contract_number ); ?></td>
      </tr>
      <tr>
        <td>Cliente</td>
        <td><?php echo esc_html( $contract->client_name ); ?></td>
      </tr>
      <?php if ( $contract->client_company ) : ?>
      <tr>
        <td>Empresa</td>
        <td><?php echo esc_html( $contract->client_company ); ?></td>
      </tr>
      <?php endif; ?>
      <tr>
        <td>DNI / CUIT declarado</td>
        <td><?php echo esc_html( $contract->signed_dni_cuit ); ?></td>
      </tr>
    </table>

    <!-- Evidencia de firma -->
    <table class="cert-table">
      <tr>
        <td>Nombre firmante</td>
        <td><?php echo esc_html( $contract->signed_name ); ?></td>
      </tr>
      <tr>
        <td>Fecha y hora de firma</td>
        <td><?php echo esc_html( $signed_at_formatted ); ?> (UTC)</td>
      </tr>
      <tr>
        <td>Email verificado Google</td>
        <td><?php echo esc_html( $contract->google_email_verified ); ?></td>
      </tr>
    </table>

    <!-- Hash del documento -->
    <div class="cert-hash">
      <div class="cert-hash__label">Hash SHA-256 del documento firmado</div>
      <div class="cert-hash__value"><?php echo esc_html( $contract->contract_hash ); ?></div>
    </div>

    <!-- Nota legal -->
    <p class="cert-legal">
      Este documento certifica que el firmante identificado anteriormente aceptó el contrato
      <strong><?php echo esc_html( $contract->contract_number ); ?></strong> mediante firma electrónica.
      La aceptación fue realizada a través del sistema privado de contratos de Pylaris,
      previo inicio de sesión con la cuenta de Google verificada.
      El hash SHA-256 del documento permite verificar que el contenido no fue modificado
      después de la firma.
    </p>
  </div>
</div>

<!-- Divisor -->
<div class="cert-divider">
  ↓ &nbsp; Contenido del contrato firmado &nbsp; ↓
</div>

<!-- Contenido del contrato -->
<div class="cert-contract-content">
  <?php echo $contract->contract_html; // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>

</body>
</html>
        <?php
        return ob_get_clean();
    }

    // ----------------------------------------------------------------
    // Descarga con MPDF
    // ----------------------------------------------------------------

    /**
     * Genera y descarga el PDF usando MPDF.
     *
     * @param object $contract
     */
    private static function download_with_mpdf( $contract ) {
        require_once PC_PLUGIN_DIR . 'vendor/autoload.php';

        $mpdf = new \Mpdf\Mpdf( array(
            'margin_top'    => 0,
            'margin_right'  => 0,
            'margin_bottom' => 0,
            'margin_left'   => 0,
            'format'        => 'A4',
            'mode'          => 'utf-8',
        ) );

        $mpdf->SetTitle( 'Constancia — ' . $contract->contract_number );
        $mpdf->SetAuthor( 'Pylaris Contracts' );
        $mpdf->WriteHTML( self::build_html( $contract ) );

        $filename = 'constancia-' . sanitize_file_name( $contract->contract_number ) . '.pdf';
        $mpdf->Output( $filename, 'D' );
        exit;
    }

    // ----------------------------------------------------------------
    // Fallback: descarga como HTML imprimible
    // ----------------------------------------------------------------

    /**
     * Sirve la constancia como HTML descargable / imprimible.
     * Fallback cuando MPDF no está disponible.
     *
     * @param object $contract
     */
    private static function download_as_html( $contract ) {
        $filename = 'constancia-' . sanitize_file_name( $contract->contract_number ) . '.html';
        $html     = self::build_html( $contract );

        // Servir como descarga
        header( 'Content-Type: text/html; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: no-cache, no-store, must-revalidate' );
        header( 'Content-Length: ' . strlen( $html ) );

        echo $html; // phpcs:ignore
        exit;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Verifica si MPDF está disponible en vendor/.
     *
     * @return bool
     */
    public static function mpdf_available() {
        $autoload = PC_PLUGIN_DIR . 'vendor/autoload.php';

        if ( class_exists( '\Mpdf\Mpdf', false ) ) {
            return true;
        }

        if ( ! file_exists( $autoload ) ) {
            return false;
        }

        require_once $autoload;

        return class_exists( '\Mpdf\Mpdf', false );
    }

    /**
     * Endpoint público para descarga de constancia.
     * Se accede desde /c/{token}/constancia (Fase 6 registra la ruta).
     * Por ahora se llama directamente desde la vista contract-signed.
     *
     * @param string $token
     */
    public static function handle_download_request( $token ) {
        $token    = preg_replace( '/[^a-zA-Z0-9]/', '', $token );
        $contract = PC_DB::get_contract_by_token( $token );

        if ( ! $contract || $contract->status !== 'signed' ) {
            wp_die( esc_html__( 'Constancia no disponible.', 'pylaris-contracts' ) );
        }

        // Verificar que hay sesión válida para este contrato
        require_once PC_PLUGIN_DIR . 'includes/class-pc-auth.php';
        $session = PC_Auth::get_session( $token );

        if ( ! $session || ! PC_Auth::session_matches_contract( $contract, $session ) ) {
            wp_die( esc_html__( 'No tenés acceso a este documento.', 'pylaris-contracts' ) );
        }

        self::download( $contract );
    }
}
