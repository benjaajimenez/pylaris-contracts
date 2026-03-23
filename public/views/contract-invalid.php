<?php
defined( 'ABSPATH' ) || exit;
/**
 * Vista: contrato inválido o no encontrado.
 * Token inexistente, contrato cancelado, o estado no público.
 */

ob_start();
?>
<div class="pc-state">
    <div class="pc-state__icon pc-state__icon--gray">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
             stroke="#6c757d" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
    </div>

    <h1 class="pc-state__title">Enlace no disponible</h1>

    <p class="pc-state__text">
        El enlace que usaste no corresponde a ningún contrato activo.<br>
        Verificá que hayas copiado el link completo.
    </p>

    <p style="font-size:13px;color:#aaa;">
        Si recibiste este link de Pylaris y creés que hay un error,<br>
        comunicáte directamente para obtener uno nuevo.
    </p>
</div>
<?php
$pc_content    = ob_get_clean();
$pc_page_title = 'Enlace no disponible';
$pc_body_class = 'pc-body--invalid';
require __DIR__ . '/_layout.php';
