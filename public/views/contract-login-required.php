<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: login requerido
 *
 * Variables disponibles:
 * @var object      $contract
 * @var string|null $auth_error  Mensaje de error de autenticación (de URL ?pc_error=)
 */

// Encolar el script de Google Sign-In
wp_enqueue_script(
    'google-gsi',
    'https://accounts.google.com/gsi/client',
    array(),
    null,
    false // en <head> para que esté disponible al cargar
);

ob_start();
?>

<!--
    One Tap de Google — se activa automáticamente si el usuario ya tiene
    sesión de Google activa en el navegador.
    El data-callback apunta a la función global definida en public.js.
-->
<?php $client_id = esc_attr( get_option( 'pc_google_client_id', '' ) ); ?>
<?php if ( $client_id ) : ?>
    <div id="g_id_onload"
         data-client_id="<?php echo $client_id; ?>"
         data-callback="pcHandleGoogleCredential"
         data-auto_select="false"
         data-cancel_on_tap_outside="true">
    </div>
<?php endif; ?>

<div class="pc-state">
    <div class="pc-state__icon pc-state__icon--orange">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#FF6B35" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
        </svg>
    </div>

    <h1 class="pc-state__title">Entrar para ver el contrato</h1>

    <p class="pc-state__text">
        Este contrato fue asignado a una cuenta específica.<br>
        Iniciá sesión con Google para continuar.
    </p>

    <?php if ( ! empty( $auth_error ) ) : ?>
        <div class="pc-alert pc-alert--error" style="margin-bottom:24px;text-align:left;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                 style="flex-shrink:0;margin-top:1px">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?php echo esc_html( $auth_error ); ?>
        </div>
    <?php endif; ?>

    <button class="pc-btn pc-btn--google" id="pc-btn-google" type="button">
        <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.875 2.684-6.615z" fill="#4285F4"/>
            <path d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z" fill="#34A853"/>
            <path d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
            <path d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z" fill="#EA4335"/>
        </svg>
        Continuar con Google
    </button>

    <?php if ( ! $client_id ) : ?>
        <p style="margin-top:20px;font-size:12px;color:#f0a;background:#fff0f5;padding:8px 12px;border-radius:4px;">
            Google Client ID no configurado. Ir a <strong>Admin → Contratos → Configuración</strong>.
        </p>
    <?php endif; ?>

    <?php if ( ! empty( $contract ) ) : ?>
        <p style="margin-top:32px;font-size:12px;color:#aaa;">
            <?php echo esc_html( sprintf(
                'Contrato %s · Solo el titular autorizado puede acceder.',
                $contract->contract_number ?? ''
            ) ); ?>
        </p>
    <?php endif; ?>
</div>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Acceso al contrato';
$pc_body_class = 'pc-body--login';
require __DIR__ . '/_layout.php';
