jQuery(function($) {
    'use strict';

    // Log de Diagnóstico: Confirma que o ficheiro admin.js foi carregado e está a ser executado.
    console.log('BlueSendMail: Ficheiro admin.js carregado com sucesso.');

    /**
     * Lógica para a página de configurações
     */
    if ($('#bsm_mailer_type').length) {
        function toggleMailerFields() {
            var mailerType = $('#bsm_mailer_type').val();
            $('.bsm-smtp-option').closest('tr').hide();
            $('.bsm-sendgrid-option').closest('tr').hide();
            if (mailerType === 'smtp') {
                $('.bsm-smtp-option').closest('tr').show();
            } else if (mailerType === 'sendgrid') {
                $('.bsm-sendgrid-option').closest('tr').show();
            }
        }
        toggleMailerFields();
        $('#bsm_mailer_type').on('change', toggleMailerFields);
    }

    /**
     * Lógica para a página de edição de automações
     */
    if ($('#bsm-automation-steps-container').length) {
        let stepIndex = $('.bsm-automation-step').length;

        // Ativa a funcionalidade de reordenar com jQuery UI Sortable
        if (typeof $.fn.sortable !== 'undefined') {
            $('#bsm-automation-steps-container').sortable({
                handle: '.bsm-step-reorder-handle',
                axis: 'y',
                update: function(event, ui) {
                    updateStepNumbers();
                }
            });
        }

        // Adicionar um novo passo
        $('#bsm-add-automation-step').on('click', function() {
            let template = $('#bsm-automation-step-template').html();
            template = template.replace(/__INDEX__/g, stepIndex).replace(/__NUMBER__/g, stepIndex + 1);
            $('#bsm-automation-steps-container').append(template);
            stepIndex++;
            updateStepNumbers();
        });

        // Remover um passo
        $('#bsm-automation-steps-container').on('click', '.bsm-remove-step', function() {
            if ($('.bsm-automation-step').length > 1) {
                $(this).closest('.bsm-automation-step').remove();
                updateStepNumbers();
            } else {
                alert('A automação deve ter pelo menos um passo.');
            }
        });

        // Lógica para mostrar/esconder os seletores de ação
        $('#bsm-automation-steps-container').on('change', '.bsm-action-type-selector', function() {
            let selectedAction = $(this).val();
            let $step = $(this).closest('.bsm-automation-step');
            
            $step.find('.bsm-action-meta-selector').hide();

            if (selectedAction === 'send_campaign') {
                $step.find('.bsm-action-meta-campaign').show();
            } else if (selectedAction === 'add_to_list' || selectedAction === 'remove_from_list') {
                $step.find('.bsm-action-meta-list').show();
            }
        });

        function updateStepNumbers() {
            $('.bsm-automation-step').each(function(index) {
                // Atualiza o número visual
                $(this).find('.bsm-step-number').text((index + 1) + '. ');
                
                // Mostra/esconde o seletor de delay com base na posição
                if (index === 0) {
                    $(this).addClass('bsm-first-step');
                    $(this).find('.bsm-step-delay-selector').hide();
                    $(this).find('.bsm-step-immediate-selector').show();
                } else {
                    $(this).removeClass('bsm-first-step');
                    $(this).find('.bsm-step-delay-selector').show();
                    $(this).find('.bsm-step-immediate-selector').hide();
                }

                // Atualiza os nomes dos campos para manter a ordem correta no POST
                $(this).find('input, select').each(function() {
                    let name = $(this).attr('name');
                    if (name) {
                        let newName = name.replace(/steps\[.*?\]/, 'steps[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        }
        // Executa a função uma vez no carregamento da página para acertar a UI inicial
        updateStepNumbers();
    }

    /**
     * Lógica para a tela de edição de campanha
     */
    if (typeof bsm_admin_data !== 'undefined' && bsm_admin_data.is_campaign_editor) {
        if ($('#bsm-lists-select').length) {
            $('#bsm-lists-select').select2({ placeholder: "Selecione as listas de destinatários", allowClear: true });
        }
        var scheduleCheckbox = $('#bsm-schedule-enabled'), scheduleFields = $('#bsm-schedule-fields'), sendNowButton = $('#bsm-send-now-button'), scheduleButton = $('#bsm-schedule-button');
        function toggleScheduleUI() { scheduleCheckbox.is(':checked') ? (scheduleFields.slideDown(), sendNowButton.hide(), scheduleButton.show()) : (scheduleFields.slideUp(), sendNowButton.show(), scheduleButton.hide()); }
        toggleScheduleUI();
        scheduleCheckbox.on('change', toggleScheduleUI);
        $('.bsm-merge-tag').on('click', function() {
            var tag = $(this).data('tag');
            if (typeof tinymce !== 'undefined' && tinymce.get('bsm-content') && !tinymce.get('bsm-content').isHidden()) {
                tinymce.get('bsm-content').execCommand('mceInsertContent', false, ' ' + tag + ' ');
            } else {
                var editor = $('#bsm-content'), currentVal = editor.val(), cursorPos = editor.prop('selectionStart'), newVal = currentVal.substring(0, cursorPos) + ' ' + tag + ' ' + currentVal.substring(cursorPos);
                editor.val(newVal); editor.focus(); editor.prop('selectionStart', cursorPos + tag.length + 2); editor.prop('selectionEnd', cursorPos + tag.length + 2);
            }
        });
        $('.bsm-template-card').on('click', function(e){
            e.preventDefault();
            var $card = $(this), templateId = $card.data('template-id');
            $('.bsm-template-card').removeClass('active');
            $card.addClass('active');
            var $editorContainer = $('#wp-bsm-content-wrap');
            $editorContainer.css('opacity', 0.5);
            $.ajax({
                url: bsm_admin_data.ajax_url, type: 'POST', data: { action: 'bsm_get_template_content', template_id: templateId, nonce: bsm_admin_data.nonce },
                success: function(response) {
                    if (response.success) {
                        if (typeof tinymce !== 'undefined' && tinymce.get('bsm-content')) { tinymce.get('bsm-content').setContent(response.data.content); } else { $('#bsm-content').val(response.data.content); }
                    } else { alert('Erro ao carregar o template.'); }
                },
                error: function() { alert('Erro de comunicação ao carregar o template.'); },
                complete: function() { $editorContainer.css('opacity', 1); }
            });
        });
    }

    /**
     * Lógica para a página de importação
     */
    if (typeof bsm_admin_data !== 'undefined' && bsm_admin_data.is_import_page) {
        $('select[name^="column_map"]').each(function() {
            var labelText = $(this).closest('tr').find('th label').text().toLowerCase();
            if (labelText.includes('mail')) { $(this).val('email'); } 
            else if (labelText.includes('nome') || labelText.includes('first')) { $(this).val('first_name'); } 
            else if (labelText.includes('sobrenome') || labelText.includes('last') || labelText.includes('apelido')) { $(this).val('last_name'); }
        });
    }

    /**
     * Lógica para os gráficos
     */
    function initializeChart(ctx, type, data, options) { if (ctx) { new Chart(ctx, { type: type, data: data, options: options }); } }
    if (typeof bsm_admin_data !== 'undefined' && bsm_admin_data.is_reports_page && typeof bsm_admin_data.chart_data !== 'undefined') {
        var reportCtx = document.getElementById('bsm-report-chart'), chartData = bsm_admin_data.chart_data, notOpened = Math.max(0, chartData.sent - chartData.opens), opens_only = Math.max(0, chartData.opens - chartData.clicks);
        initializeChart(reportCtx, 'doughnut', { labels: [ chartData.labels.not_opened, chartData.labels.opened, chartData.labels.clicked ], datasets: [{ label: 'Visão Geral da Campanha', data: [notOpened, opens_only, chartData.clicks], backgroundColor: [ 'rgb(220, 220, 220)', 'rgb(54, 162, 235)', 'rgb(75, 192, 192)' ], hoverOffset: 4 }] }, { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' }, title: { display: true, text: 'Desempenho da Campanha' } } });
    }
    if (typeof bsm_admin_data !== 'undefined' && bsm_admin_data.is_dashboard_page) {
        var growthCtx = document.getElementById('bsm-growth-chart');
        if (typeof bsm_admin_data.growth_chart_data !== 'undefined') {
            initializeChart(growthCtx, 'line', { labels: bsm_admin_data.growth_chart_data.labels, datasets: [{ label: 'Novos Contatos', data: bsm_admin_data.growth_chart_data.data, fill: true, backgroundColor: 'rgba(54, 162, 235, 0.2)', borderColor: 'rgb(54, 162, 235)', tension: 0.1 }] }, { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } });
        }
        var perfCtx = document.getElementById('bsm-performance-chart');
        if (typeof bsm_admin_data.performance_chart_data !== 'undefined') {
            var perfData = bsm_admin_data.performance_chart_data, perfNotOpened = Math.max(0, perfData.sent - perfData.opens), perfOpensOnly = Math.max(0, perfData.opens - perfData.clicks);
            initializeChart(perfCtx, 'doughnut', { labels: [ perfData.labels.not_opened, perfData.labels.opened, perfData.labels.clicked ], datasets: [{ label: 'Performance Geral', data: [perfNotOpened, perfOpensOnly, perfData.clicks], backgroundColor: [ 'rgb(220, 220, 220)', 'rgb(54, 162, 235)', 'rgb(75, 192, 192)' ], hoverOffset: 4 }] }, { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } });
        }
    }
});

