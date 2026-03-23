<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: acceso denegado
 * El usuario autenticó con Google pero su email no coincide con el asignado.
 *
 * Variables disponibles:
 * @var string $google_email  Email con el que intentó autenticarse.
 */

ob_start();
?>
<div class="pc-state">
    <div class="pc-state__icon pc-state__icon--red">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#dc3545" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
    </div>

    <h1 class="pc-state__title">Acceso denegado</h1>

    <p class="pc-state__text">
        Este contrato fue asignado a otra cuenta y no puede ser visualizado desde este correo.
    </p>

    <?php if ( ! empty( $google_email ) ) : ?>
        <p style="font-size:13px;color:#aaa;margin-top:-16px;margin-bottom:28px;">
            Cuenta utilizada: <strong><?php echo esc_html( $google_email ); ?></strong>
        </p>
    <?php endif; ?>

    <button class="pc-btn pc-btn--google" id="pc-btn-switch-account" type="button">
        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
            <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
            <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
        </svg>
        Ingresar con otra cuenta
    </button>

    <p style="margin-top:24px;font-size:13px;color:#aaa;">
        Si creés que esto es un error, contactá a Pylaris.
    </p>
</div>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Acceso denegado';
$pc_body_class = 'pc-body--denied';
require __DIR__ . '/_layout.php';
