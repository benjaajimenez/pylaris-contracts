<?php
defined( 'ABSPATH' ) || exit;

/**
 * PC_Template
 *
 * Responsabilidades:
 * - Cargar el template base del contrato.
 * - Reemplazar variables {{var}} con los datos reales.
 * - Generar el HTML final listo para guardar en contract_html.
 *
 * Variables soportadas (definidas en 05-templates_variables-contrato.txt):
 *
 * Identificación:  {{contract_number}}, {{contract_date}}
 * Cliente:         {{client_name}}, {{client_email}}, {{client_dni_cuit}}, {{client_company}}
 * Proyecto:        {{project_title}}, {{project_scope}}, {{project_pages}}
 * Comercial:       {{project_amount}}, {{project_currency}}
 * Plazos:          {{delivery_time}}, {{revision_rounds}}
 * Legales:         {{jurisdiction}}
 * Sistema:         {{expires_at}}
 *
 * Bloques condicionales:
 * {{#client_company}}...{{/client_company}}  — renderiza si client_company no está vacío
 * {{^client_company}}...{{/client_company}}  — renderiza si client_company está vacío
 * {{#project_title}}...{{/project_title}}    — ídem para project_title
 * {{^project_title}}...{{/project_title}}
 */
class PC_Template {

    /**
     * Genera el HTML final del contrato a partir del template base y los datos.
     *
     * @param  array $data  Datos del contrato (sanitizados).
     * @return string       HTML del contrato con todas las variables reemplazadas.
     */
    public static function render( array $data ) {
        $template = self::load_template();
        $html     = self::replace_variables( $template, $data );
        $html     = self::process_conditionals( $html, $data );
        $html     = self::cleanup( $html );

        return $html;
    }

    /**
     * Previsualiza el template con datos de ejemplo.
     * Útil para el admin antes de crear un contrato real.
     *
     * @return string HTML de previsualización.
     */
    public static function preview() {
        return self::render( self::get_sample_data() );
    }

    /**
     * Genera la lista de variables disponibles en el template.
     *
     * @return array  { variable, description, required }
     */
    public static function get_variable_list() {
        return array(
            array( 'variable' => '{{contract_number}}',  'description' => 'Número de contrato (ej: PC-2026-0001)', 'required' => true ),
            array( 'variable' => '{{contract_date}}',    'description' => 'Fecha de emisión del contrato',         'required' => true ),
            array( 'variable' => '{{client_name}}',      'description' => 'Nombre completo del cliente',           'required' => true ),
            array( 'variable' => '{{client_email}}',     'description' => 'Email del cliente',                     'required' => true ),
            array( 'variable' => '{{client_dni_cuit}}',  'description' => 'DNI o CUIT del cliente',                'required' => true ),
            array( 'variable' => '{{client_company}}',   'description' => 'Empresa del cliente (opcional)',        'required' => false ),
            array( 'variable' => '{{project_title}}',    'description' => 'Título del proyecto (opcional)',        'required' => false ),
            array( 'variable' => '{{project_scope}}',    'description' => 'Descripción del alcance del proyecto',  'required' => true ),
            array( 'variable' => '{{project_amount}}',   'description' => 'Monto del proyecto',                    'required' => true ),
            array( 'variable' => '{{project_currency}}', 'description' => 'Moneda (USD, ARS, EUR)',                'required' => true ),
            array( 'variable' => '{{delivery_time}}',    'description' => 'Plazo de entrega (ej: 3 semanas)',      'required' => true ),
            array( 'variable' => '{{revision_rounds}}',  'description' => 'Cantidad de rondas de revisión',        'required' => true ),
            array( 'variable' => '{{jurisdiction}}',     'description' => 'Jurisdicción legal',                    'required' => true ),
            array( 'variable' => '{{expires_at}}',       'description' => 'Fecha de vencimiento (opcional)',       'required' => false ),
        );
    }

    // ----------------------------------------------------------------
    // Internos
    // ----------------------------------------------------------------

    /**
     * Carga el template base desde el archivo .php del directorio templates/.
     * Extrae solo el HTML (descarta la primera línea {{CONTRACT_HTML}}).
     *
     * @return string
     */
    private static function load_template() {
        $file = PC_PLUGIN_DIR . 'templates/base-contract-template.php';

        if ( ! file_exists( $file ) ) {
            return '<p>Template no encontrado.</p>';
        }

        $raw = file_get_contents( $file );

        // Quitar la primer línea marcadora {{CONTRACT_HTML}}
        $raw = preg_replace( '/^{{CONTRACT_HTML}}\s*/m', '', $raw, 1 );

        return $raw;
    }

    /**
     * Reemplaza todas las variables {{var}} con sus valores.
     *
     * @param  string $template
     * @param  array  $data
     * @return string
     */
    private static function replace_variables( $template, array $data ) {
        $now = current_time( 'mysql', true );

        // Valores derivados
        $data['contract_date'] = $data['contract_date']
            ?? PC_Helpers::format_date( $now, 'd/m/Y' );

        $data['project_amount'] = isset( $data['project_amount'] )
            ? number_format( (float) $data['project_amount'], 2, ',', '.' )
            : '';

        // Reemplazar cada variable
        foreach ( $data as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $template = str_replace(
                    '{{' . $key . '}}',
                    esc_html( (string) $value ),
                    $template
                );
            }
        }

        return $template;
    }

    /**
     * Procesa bloques condicionales {{#var}}...{{/var}} y {{^var}}...{{/var}}.
     *
     * {{#var}}content{{/var}}  → muestra content si $data['var'] no está vacío
     * {{^var}}content{{/var}}  → muestra content si $data['var'] está vacío
     *
     * @param  string $template
     * @param  array  $data
     * @return string
     */
    private static function process_conditionals( $template, array $data ) {
        // Bloques positivos: {{#var}}...{{/var}}
        $template = preg_replace_callback(
            '/\{\{#([a-z_]+)\}\}(.*?)\{\{\/\1\}\}/s',
            function ( $matches ) use ( $data ) {
                $key = $matches[1];
                return ! empty( $data[ $key ] ) ? $matches[2] : '';
            },
            $template
        );

        // Bloques negativos: {{^var}}...{{/var}}
        $template = preg_replace_callback(
            '/\{\{\^([a-z_]+)\}\}(.*?)\{\{\/\1\}\}/s',
            function ( $matches ) use ( $data ) {
                $key = $matches[1];
                return empty( $data[ $key ] ) ? $matches[2] : '';
            },
            $template
        );

        return $template;
    }

    /**
     * Limpia variables no reemplazadas que hayan quedado en el template.
     *
     * @param  string $html
     * @return string
     */
    private static function cleanup( $html ) {
        // Eliminar variables no reemplazadas
        $html = preg_replace( '/\{\{[a-z_#\^\/]+\}\}/s', '', $html );

        return $html;
    }

    /**
     * Datos de ejemplo para previsualización.
     *
     * @return array
     */
    public static function get_sample_data() {
        return array(
            'contract_number'  => 'PC-2026-0001',
            'contract_date'    => date( 'd/m/Y' ),
            'client_name'      => 'María García',
            'client_email'     => 'maria@empresa.com',
            'client_dni_cuit'  => '27-30123456-4',
            'client_company'   => 'Empresa Ejemplo S.R.L.',
            'project_title'    => 'Sitio Web Corporativo',
            'project_scope'    => 'Diseño y desarrollo de sitio web corporativo de hasta 6 páginas (Home, Servicios, Nosotros, Portfolio, Blog, Contacto). Diseño responsivo, optimización básica de SEO y capacitación para gestión de contenido.',
            'project_amount'   => '1500.00',
            'project_currency' => 'USD',
            'delivery_time'    => '4 semanas',
            'revision_rounds'  => '2',
            'jurisdiction'     => 'Buenos Aires, Argentina',
            'expires_at'       => '',
        );
    }
}
