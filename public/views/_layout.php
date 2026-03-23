<?php
/**
 * Layout base para todas las vistas públicas del sistema de contratos.
 *
 * Variables disponibles en las vistas hijas:
 * @var string $pc_page_title  Título de la pestaña del navegador.
 * @var string $pc_body_class  Clase CSS adicional para el body.
 * @var string $pc_content     HTML del contenido principal (ob_get_clean()).
 */
defined( 'ABSPATH' ) || exit;
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( $pc_page_title ?? 'Pylaris Contracts' ); ?> — Pylaris</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<?php wp_head(); ?>
</head>
<body class="pc-body <?php echo esc_attr( $pc_body_class ?? '' ); ?>">

<div class="pc-shell">

    <!-- Header mínimo -->
    <header class="pc-header">
        <div class="pc-header__inner">
            <div class="pc-header__brand">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024" height="32" aria-hidden="true">
                    <g transform="matrix(2.7237026,0,0,2.8668624,-921.28764,-955.55865)">
                        <path fill="#ededed" d="M 385.46197,688.75 C 385.20058,688.0625 385.1022,608.3 385.24336,511.5 L 385.5,335.5 412.25,335.23367 439,334.96733 V 512.48367 690 h -26.53138 c -20.30498,0 -26.64292,-0.29335 -27.00665,-1.25 z m 99.99799,0 c -0.26249,-0.6875 -0.36087,-34.325 -0.21861,-74.75 L 485.5,540.5 508,527.10216 c 12.375,-7.36881 34.53459,-20.56259 49.24353,-29.31951 l 26.74353,-15.92167 0.25647,-73.18049 L 584.5,335.5 h 27 27 v 86.47828 86.47829 l -17.63907,10.52171 c -9.70148,5.78695 -20.7004,12.32172 -24.44204,14.52172 -9.25232,5.44017 -9.55754,5.62244 -29.33943,17.52141 -9.58129,5.76322 -18.58129,11.16474 -20,12.00337 -1.4187,0.83862 -3.81696,2.35288 -5.32946,3.36501 L 539,568.23002 V 629.11501 690 h -26.53138 c -20.3039,0 -26.64341,-0.2934 -27.00866,-1.25 z m -0.21609,-275.5 0.25613,-77.75 26.13709,-0.26638 26.13709,-0.26639 0.72931,24.31253 c 0.40112,13.37189 0.49194,41.36815 0.20181,62.21391 l -0.5275,37.90138 -2.3389,1.66681 C 532.41353,463.50293 486.23479,491 485.56061,491 c -0.31508,0 -0.45761,-34.9875 -0.31674,-77.75 z"/>
                        <path fill="#f3692a" d="m 639.08479,556.01765 -2.29223,1.48184 c -1.26092,0.81515 -7.91721,4.93063 -14.79221,9.14546 -6.875,4.21482 -18.12499,11.20386 -24.99999,15.53117 l -12.49999,7.86819 -0.26206,47.98331 c -0.24481,44.82782 -0.14635,48.07177 1.49981,49.32323 2.66473,2.0258 13.75746,0.97397 19.26202,-1.8264 8.34707,-4.24646 24.47888,-14.21182 27.339,-16.88836 6.3821,-5.97246 6.13221,-3.62496 6.45317,-60.64222 z"/>
                    </g>
                </svg>
                <span class="pc-header__name">Pyla<em>ris</em></span>
            </div>
            <div class="pc-header__label">Acuerdo de trabajo</div>
        </div>
    </header>

    <!-- Contenido principal -->
    <main class="pc-main">
        <?php echo $pc_content ?? ''; // HTML ya escapado en cada vista ?>
    </main>

    <!-- Footer mínimo -->
    <footer class="pc-footer">
        <span>pylaris.com</span>
        <span>·</span>
        <span>Documento privado</span>
    </footer>

</div><!-- .pc-shell -->

<?php wp_footer(); ?>
</body>
</html>
