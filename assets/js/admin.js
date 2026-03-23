/* Pylaris Contracts — Admin JS — Fase 6 */
(function ($) {
    'use strict';

    var cfg = window.pcAdmin || {};

    // ----------------------------------------------------------------
    // Generar HTML desde template base
    // ----------------------------------------------------------------
    $(document).on('click', '#pc-btn-generate-template', function () {
        var $btn    = $(this);
        var $status = $('#pc-template-status');
        var $html   = $('#contract_html');

        // Recolectar datos del formulario
        var data = {
            action:           'pc_generate_template',
            nonce:            cfg.templateNonce,
            contract_number:  'PC-PREVIEW',
            client_name:      $('#client_name').val(),
            client_email:     $('#client_email').val(),
            client_dni_cuit:  $('#client_dni_cuit').val(),
            client_company:   $('#client_company').val(),
            project_title:    $('#project_title').val(),
            project_scope:    $('#project_scope').val(),
            project_amount:   $('#project_amount').val(),
            project_currency: $('select[name="project_currency"]').val(),
            delivery_time:    $('#delivery_time').val(),
            revision_rounds:  $('#revision_rounds').val(),
            jurisdiction:     $('#jurisdiction').val(),
        };

        // Validación mínima
        if ( ! data.client_name || ! data.project_scope ) {
            $status.text('Completá al menos nombre del cliente y alcance del proyecto.').css('color','#842029');
            return;
        }

        $btn.prop('disabled', true).text(cfg.generatingText || 'Generando...');
        $status.text('').css('color','#888');

        $.post(cfg.ajaxUrl, data, function (response) {
            if (response.success && response.data && response.data.html) {
                $html.val(response.data.html);
                $status.text(cfg.generatedText || 'Generado.').css('color','#155724');
            } else {
                $status.text(cfg.errorText || 'Error al generar.').css('color','#842029');
            }
        }).fail(function () {
            $status.text(cfg.errorText || 'Error de conexión.').css('color','#842029');
        }).always(function () {
            $btn.prop('disabled', false).text('⚙ Generar desde template');
        });
    });

}(jQuery));

