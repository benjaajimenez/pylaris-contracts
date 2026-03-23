<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: contrato firmado — pantalla de confirmación.
 * Se muestra tanto al volver a entrar como tras firmar.
 *
 * Variables disponibles:
 * @var object $contract
 */

ob_start();
?>
<div class="pc-state" style="max-width:600px;">
    <div class="pc-state__icon pc-state__icon--green">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>

    <h1 class="pc-state__title">Contrato firmado correctamente</h1>

    <p class="pc-state__text">
        Tu aceptación fue registrada y el documento quedó marcado como firmado.
    </p>

    <?php if ( ! empty( $contract ) ) : ?>
        <div class="pc-success-meta">
            <div class="pc-success-meta__row">
                <span class="pc-success-meta__label">N° de contrato</span>
                <span class="pc-success-meta__value"><?php echo esc_html( $contract->contract_number ); ?></span>
            </div>
            <?php if ( $contract->signed_at ) : ?>
                <div class="pc-success-meta__row">
                    <span class="pc-success-meta__label">Fecha y hora</span>
                    <span class="pc-success-meta__value">
                        <?php echo esc_html( PC_Helpers::format_date( $contract->signed_at, 'd/m/Y H:i' ) ); ?>
                    </span>
                </div>
            <?php endif; ?>
            <?php if ( $contract->google_email_verified ) : ?>
                <div class="pc-success-meta__row">
                    <span class="pc-success-meta__label">Email verificado</span>
                    <span class="pc-success-meta__value"><?php echo esc_html( $contract->google_email_verified ); ?></span>
                </div>
            <?php endif; ?>
            <?php if ( $contract->signed_name ) : ?>
                <div class="pc-success-meta__row">
                    <span class="pc-success-meta__label">Firmado por</span>
                    <span class="pc-success-meta__value"><?php echo esc_html( $contract->signed_name ); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p style="margin-top:24px;font-size:13px;color:#aaa;line-height:1.7;">
        Recibirás una copia por email. Guardá este número para tus registros.
    </p>

    <?php if ( ! empty( $contract ) && $contract->token ) : ?>
        <div style="margin-top:28px;">
            <a href="<?php echo esc_url( home_url( '/c/' . $contract->token . '/constancia' ) ); ?>"
               class="pc-btn pc-btn--primary"
               style="text-decoration:none;display:inline-flex;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                     style="flex-shrink:0;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Descargar constancia
            </a>
        </div>
    <?php endif; ?>
</div>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Contrato firmado';
$pc_body_class = 'pc-body--signed';
require __DIR__ . '/_layout.php';
