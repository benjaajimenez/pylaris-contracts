<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: contrato vencido.
 *
 * Variables disponibles:
 * @var object $contract
 */

ob_start();
?>
<div class="pc-state">
    <div class="pc-state__icon pc-state__icon--red">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#842029" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
        </svg>
    </div>

    <h1 class="pc-state__title">Contrato vencido</h1>

    <p class="pc-state__text">
        Este contrato ya no se encuentra disponible para firma.<br>
        Solicitá una nueva versión actualizada.
    </p>

    <?php if ( ! empty( $contract->expires_at ) ) : ?>
        <p style="font-size:13px;color:#aaa;margin-top:-16px;margin-bottom:28px;">
            Venció el <?php echo esc_html( PC_Helpers::format_date( $contract->expires_at, 'd/m/Y' ) ); ?>
        </p>
    <?php endif; ?>

    <p style="font-size:13px;color:#aaa;">
        Contactá a Pylaris para recibir un nuevo link de contrato.
    </p>
</div>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Contrato vencido';
$pc_body_class = 'pc-body--expired';
require __DIR__ . '/_layout.php';
