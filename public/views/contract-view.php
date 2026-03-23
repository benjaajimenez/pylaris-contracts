<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: contrato — lectura y aceptación.
 * Solo llega aquí si el usuario pasó la validación de identidad (Fase 4).
 *
 * Variables disponibles:
 * @var object $contract       Objeto del contrato (status = pending).
 * @var string $google_email   Email autenticado con Google.
 * @var string $google_name    Nombre del usuario autenticado.
 * @var array  $sign_error     Array con 'message' si hubo error al firmar.
 */

$nonce      = PC_Security::create_nonce( PC_Security::NONCE_SIGN_CONTRACT );
$sign_error = $sign_error ?? null;

ob_start();
?>

<!-- Barra de estado -->
<div class="pc-card">
    <div class="pc-status-bar pc-status-bar--pending">
        <span class="pc-status-bar__dot"></span>
        Pendiente de aceptación · <?php echo esc_html( $contract->contract_number ); ?>
    </div>

    <!-- Cuerpo del contrato -->
    <div class="pc-contract-wrap">

        <!-- Datos de contexto -->
        <div class="pc-contract-meta">
            <div class="pc-contract-meta__cell">
                <div class="pc-contract-meta__label">Cliente</div>
                <div><?php echo esc_html( $contract->client_name ); ?></div>
            </div>
            <div class="pc-contract-meta__cell">
                <div class="pc-contract-meta__label">Cuenta verificada</div>
                <div><?php echo esc_html( $google_email ?? $contract->client_email ); ?></div>
            </div>
            <?php if ( $contract->project_title ) : ?>
                <div class="pc-contract-meta__cell">
                    <div class="pc-contract-meta__label">Proyecto</div>
                    <div><?php echo esc_html( $contract->project_title ); ?></div>
                </div>
            <?php endif; ?>
            <div class="pc-contract-meta__cell">
                <div class="pc-contract-meta__label">Monto</div>
                <div>
                    <?php echo esc_html( number_format( (float) $contract->project_amount, 2 ) ); ?>
                    <small style="color:#888"><?php echo esc_html( $contract->project_currency ); ?></small>
                </div>
            </div>
        </div>

        <!-- HTML del contrato (inmutable, solo lectura) -->
        <div class="pc-contract-html">
            <?php
            /*
             * Se imprime el contract_html directamente.
             * Este HTML fue generado y guardado por el admin de Pylaris.
             * No viene del cliente — es seguro renderizarlo.
             * Se sanitizó con wp_kses_post al guardarlo.
             */
            echo $contract->contract_html; // phpcs:ignore WordPress.Security.EscapeOutput
            ?>
        </div>

        <!-- Bloque de aceptación -->
        <div class="pc-accept-block">
            <h2 class="pc-accept-block__title">Aceptación del acuerdo</h2>

            <?php if ( $sign_error ) : ?>
                <div class="pc-alert pc-alert--error">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo esc_html( $sign_error['message'] ); ?>
                </div>
            <?php endif; ?>

            <form id="pc-sign-form" method="post" action="" novalidate>
                <input type="hidden" name="pc_action"  value="sign_contract">
                <input type="hidden" name="_wpnonce"   value="<?php echo esc_attr( $nonce ); ?>">
                <input type="hidden" name="token"      value="<?php echo esc_attr( $contract->token ); ?>">

                <div class="pc-field">
                    <label for="signed_name">Nombre completo *</label>
                    <input type="text" id="signed_name" name="signed_name"
                           placeholder="Ingresá tu nombre completo tal como aparece en tu DNI"
                           required autocomplete="name"
                           value="<?php echo esc_attr( $_POST['signed_name'] ?? $google_name ?? '' ); ?>">
                </div>

                <div class="pc-field">
                    <label for="signed_dni_cuit">DNI / CUIT *</label>
                    <input type="text" id="signed_dni_cuit" name="signed_dni_cuit"
                           placeholder="Ej: 30123456 o 20-30123456-9"
                           required autocomplete="off"
                           value="<?php echo esc_attr( $_POST['signed_dni_cuit'] ?? '' ); ?>">
                </div>

                <label class="pc-field--checkbox" for="accepted_checkbox">
                    <input type="checkbox" id="accepted_checkbox" name="accepted_checkbox" value="1" required>
                    <span>Declaro haber leído y aceptado íntegramente este acuerdo.</span>
                </label>

                <button type="submit" class="pc-btn pc-btn--primary pc-btn--large" id="pc-btn-sign">
                    Aceptar y firmar
                </button>

                <p style="font-size:12px;color:#aaa;margin-top:16px;text-align:center;line-height:1.6;">
                    Al hacer clic en "Aceptar y firmar" se registrará tu identidad verificada por Google,
                    dirección IP y marca de tiempo como evidencia de tu aceptación.
                </p>
            </form>
        </div>

    </div><!-- .pc-contract-wrap -->
</div><!-- .pc-card -->

<script>
(function() {
    var form     = document.getElementById('pc-sign-form');
    var btn      = document.getElementById('pc-btn-sign');
    var checkbox = document.getElementById('accepted_checkbox');
    var name     = document.getElementById('signed_name');
    var dni      = document.getElementById('signed_dni_cuit');

    if ( ! form ) return;

    form.addEventListener('submit', function(e) {
        var errors = [];

        if ( ! name.value.trim() )     errors.push('Ingresá tu nombre completo.');
        if ( ! dni.value.trim() )      errors.push('Ingresá tu DNI o CUIT.');
        if ( ! checkbox.checked )      errors.push('Debés aceptar el acuerdo antes de firmar.');

        if ( errors.length ) {
            e.preventDefault();
            alert( errors.join('\n') );
            return;
        }

        btn.disabled    = true;
        btn.textContent = 'Procesando...';
    });
})();
</script>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Revisar y firmar contrato';
$pc_body_class = 'pc-body--view';
require __DIR__ . '/_layout.php';
