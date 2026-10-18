/**
 * CentralVix Importador de Clientes — Admin JavaScript
 */
(function () {
    'use strict';

    if (typeof CVIX_IMPORTER === 'undefined') {
        return;
    }

    var config = CVIX_IMPORTER;
    var state = {
        importId: 0,
        headers: [],
        totalRows: 0,
        mapping: {},
        selectedFile: null,
        importing: false
    };

    /* ── Utility ── */
    function $(sel) { return document.querySelector(sel); }
    function $$(sel) { return document.querySelectorAll(sel); }

    function showAlert(message, type) {
        var modal = $('#cvixAlertModal');
        var body = $('#cvixAlertBody');
        if (!modal || !body) {
            alert(message);
            return;
        }
        var icon = type === 'error' ? 'fa-times-circle' : (type === 'success' ? 'fa-check-circle' : 'fa-info-circle');
        var color = type === 'error' ? '#dc2626' : (type === 'success' ? '#00B4D8' : '#0B3D91');
        body.innerHTML = '<i class="fas ' + icon + '" style="font-size:48px;color:' + color + ';margin-bottom:16px;display:block;"></i><p>' + message + '</p>';
        modal.style.display = 'flex';
    }

    function hideAlert() {
        var modal = $('#cvixAlertModal');
        if (modal) modal.style.display = 'none';
    }

    function ajaxPost(url, formData, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.onload = function () {
            var raw = xhr.responseText || '';
            var data = null;
            try {
                data = JSON.parse(raw);
            } catch (e) {
                data = null;
            }
            if (data) {
                if (!data.success && xhr.status >= 500 && !data.message) {
                    data.message = 'Erro interno do servidor (HTTP ' + xhr.status + ').';
                }
                callback(data);
                return;
            }
            var snippet = raw.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 180);
            var msg = 'Resposta inválida do servidor.';
            if (xhr.status === 403) {
                msg = 'Acesso negado. Recarregue a página e tente novamente.';
            } else if (xhr.status >= 500) {
                msg = 'Erro interno do servidor (HTTP ' + xhr.status + ').';
            }
            if (snippet) {
                msg += ' Detalhe: ' + snippet;
            }
            callback({ success: false, message: msg });
        };
        xhr.onerror = function () {
            callback({ success: false, message: 'Erro de conexão com o servidor.' });
        };
        xhr.send(formData);
    }

    function setStep(step) {
        $$('.cvix-step').forEach(function (el) {
            var s = parseInt(el.getAttribute('data-step'), 10);
            el.classList.remove('active', 'done');
            if (s < step) el.classList.add('done');
            if (s === step) el.classList.add('active');
        });
        $$('.cvix-wizard-panel').forEach(function (panel) {
            panel.classList.remove('active');
        });
        var panel = $('#cvixStep' + step);
        if (panel) panel.classList.add('active');
    }

    /* ── Step 1: Upload ── */
    function initUpload() {
        var zone = $('#cvixUploadZone');
        var input = $('#cvixFileInput');
        var analyzeBtn = $('#cvixAnalyzeBtn');
        if (!zone || !input) return;

        zone.addEventListener('click', function (e) {
            if (e.target !== analyzeBtn) input.click();
        });

        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('dragover');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('dragover');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });

        input.addEventListener('change', function () {
            if (input.files.length) handleFileSelect(input.files[0]);
        });

        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                uploadAndAnalyze();
            });
        }
    }

    function handleFileSelect(file) {
        var allowed = ['csv', 'xlsx', 'xls'];
        var ext = file.name.split('.').pop().toLowerCase();
        if (allowed.indexOf(ext) === -1) {
            showAlert('Formato não permitido. Use CSV, XLSX ou XLS.', 'error');
            return;
        }
        if (file.size > config.maxFileSize) {
            showAlert('Arquivo excede o tamanho máximo permitido.', 'error');
            return;
        }
        state.selectedFile = file;
        var nameEl = $('#cvixFileName');
        var selectedEl = $('#cvixFileSelected');
        var btn = $('#cvixAnalyzeBtn');
        if (nameEl) nameEl.textContent = file.name;
        if (selectedEl) selectedEl.style.display = 'inline-flex';
        if (btn) btn.disabled = false;
    }

    function uploadAndAnalyze() {
        if (!state.selectedFile) return;

        var btn = $('#cvixAnalyzeBtn');
        var progress = $('#cvixUploadProgress');
        if (btn) btn.disabled = true;
        if (progress) progress.style.display = 'block';

        var fd = new FormData();
        fd.append('token', config.csrfToken);
        fd.append('file', state.selectedFile);

        ajaxPost(config.ajaxUpload, fd, function (data) {
            if (progress) progress.style.display = 'none';
            if (btn) btn.disabled = false;

            if (!data || !data.success) {
                var msg = (data && data.message) ? data.message : 'Erro ao analisar arquivo.';
                if (msg.indexOf('endpoint foi desativado') !== -1 || msg.indexOf('Use o endpoint via admin') !== -1) {
                    msg = 'Cache desatualizado. Pressione Ctrl+F5 para recarregar a página do módulo.';
                }
                showAlert(msg, 'error');
                return;
            }

            state.importId = data.import_id;
            state.headers = data.headers;
            state.totalRows = data.total_rows;
            state.mapping = (data.auto_mapping && typeof data.auto_mapping === 'object' && !Array.isArray(data.auto_mapping))
                ? data.auto_mapping
                : {};
            persistWizardState();

            loadPreview();
        });
    }

    /* ── Step 2: Preview ── */
    function loadPreview() {
        var fd = new FormData();
        fd.append('token', config.csrfToken);
        fd.append('import_id', state.importId);

        ajaxPost(config.ajaxPreview, fd, function (data) {
            if (!data.success) {
                showAlert(data.message || 'Erro ao carregar pré-visualização.', 'error');
                return;
            }

            renderPreview(data);
            buildMappingGrid();
            setStep(2);
        });
    }

    function renderPreview(data) {
        var filenameEl = $('#cvixPreviewFilename');
        var totalEl = $('#cvixPreviewTotal');
        var head = $('#cvixPreviewHead');
        var body = $('#cvixPreviewBody');

        if (filenameEl) filenameEl.textContent = data.filename;
        if (totalEl) totalEl.textContent = data.total_rows;

        if (head) {
            head.innerHTML = '<tr>' + data.headers.map(function (h) {
                return '<th>' + escapeHtml(h) + '</th>';
            }).join('') + '</tr>';
        }

        if (body) {
            body.innerHTML = data.preview.map(function (row) {
                return '<tr>' + data.headers.map(function (h) {
                    return '<td>' + escapeHtml(row[h] || '') + '</td>';
                }).join('') + '</tr>';
            }).join('');
        }
    }

    /* ── Step 3: Mapping ── */
    function buildFieldOptions(selected) {
        selected = selected || '';
        var html = '<option value="">— Ignorar coluna —</option>';
        if (typeof CVIX_WHMCS_FIELDS !== 'undefined') {
            for (var key in CVIX_WHMCS_FIELDS) {
                if (CVIX_WHMCS_FIELDS.hasOwnProperty(key)) {
                    html += '<option value="' + escapeAttr(key) + '"' + (selected === key ? ' selected' : '') + '>' +
                        escapeHtml(CVIX_WHMCS_FIELDS[key]) + '</option>';
                }
            }
        }
        if (typeof CVIX_CUSTOM_FIELDS !== 'undefined' && CVIX_CUSTOM_FIELDS.length) {
            html += '<optgroup label="Campos personalizados">';
            CVIX_CUSTOM_FIELDS.forEach(function (cf) {
                var val = 'custom_' + cf.id;
                html += '<option value="' + val + '"' + (selected === val ? ' selected' : '') + '>' +
                    escapeHtml(cf.name) + ' (custom)</option>';
            });
            html += '</optgroup>';
        }
        return html;
    }

    function buildMappingGrid() {
        var grid = $('#cvixMappingGrid');
        if (!grid) return;

        grid.innerHTML = state.headers.map(function (col) {
            var selected = state.mapping[col] || '';
            return '<div class="cvix-mapping-row">' +
                '<div class="cvix-mapping-col-name"><i class="fas fa-columns"></i> ' + escapeHtml(col) + '</div>' +
                '<div class="cvix-mapping-arrow"><i class="fas fa-long-arrow-alt-right"></i></div>' +
                '<select class="cvix-form-control cvix-mapping-select" data-column="' + escapeAttr(col) + '">' +
                buildFieldOptions(selected) +
                '</select></div>';
        }).join('');

        grid.querySelectorAll('.cvix-mapping-select').forEach(function (sel) {
            sel.addEventListener('change', function () {
                state.mapping[sel.getAttribute('data-column')] = sel.value;
            });
        });
    }

    function collectMapping() {
        var mapping = {};
        $$('.cvix-mapping-select').forEach(function (sel) {
            var col = sel.getAttribute('data-column');
            if (col) {
                mapping[col] = sel.value || '';
            }
        });
        if (Object.keys(mapping).length === 0 && state.mapping && typeof state.mapping === 'object') {
            mapping = Object.assign({}, state.mapping);
        }
        state.mapping = mapping;
        return mapping;
    }

    function mappingHasEmail(mapping) {
        for (var col in mapping) {
            if (Object.prototype.hasOwnProperty.call(mapping, col) && mapping[col] === 'email') {
                return true;
            }
        }
        return false;
    }

    function persistWizardState() {
        try {
            sessionStorage.setItem('cvix_import_wizard', JSON.stringify({
                importId: state.importId,
                headers: state.headers,
                totalRows: state.totalRows,
                mapping: state.mapping
            }));
        } catch (e) { /* ignore */ }
    }

    function restoreWizardState() {
        try {
            var raw = sessionStorage.getItem('cvix_import_wizard');
            if (!raw) return;
            var saved = JSON.parse(raw);
            if (saved.importId) state.importId = saved.importId;
            if (saved.headers && saved.headers.length) state.headers = saved.headers;
            if (saved.totalRows) state.totalRows = saved.totalRows;
            if (saved.mapping && typeof saved.mapping === 'object') state.mapping = saved.mapping;
        } catch (e) { /* ignore */ }
    }

    function encodeMappingForPost(mapping) {
        var json = JSON.stringify(mapping || {});
        if (!json || json === 'undefined') {
            json = '{}';
        }
        var b64 = '';
        try {
            b64 = btoa(unescape(encodeURIComponent(json)));
        } catch (e) {
            b64 = '';
        }
        return { json: json, b64: b64 };
    }

    /* ── Step 4: Settings & Save ── */
    function saveMappingAndSettings(callback) {
        if (!state.importId) {
            showAlert('Sessão da importação expirada. Faça o upload do arquivo novamente.', 'error');
            setStep(1);
            return;
        }

        var mapping = collectMapping();
        if (!mappingHasEmail(mapping)) {
            showAlert('Mapeie ao menos uma coluna para o campo E-mail (email) na etapa 3.', 'error');
            setStep(3);
            return;
        }

        persistWizardState();
        var encoded = encodeMappingForPost(mapping);

        var fd = new FormData();
        fd.append('token', config.csrfToken);
        fd.append('action', 'save_mapping');
        fd.append('import_id', state.importId);
        fd.append('mapping', encoded.json);
        if (encoded.b64) {
            fd.append('mapping_b64', encoded.b64);
        }

        fd.append('skip_existing_email', $('#opt_skip_existing') && $('#opt_skip_existing').checked ? '1' : '');
        fd.append('update_existing', $('#opt_update_existing') && $('#opt_update_existing').checked ? '1' : '');
        fd.append('validate_email', $('#opt_validate_email') && $('#opt_validate_email').checked ? '1' : '');
        fd.append('trim_spaces', $('#opt_trim_spaces') && $('#opt_trim_spaces').checked ? '1' : '');
        fd.append('remove_invalid_chars', $('#opt_remove_invalid') && $('#opt_remove_invalid').checked ? '1' : '');
        fd.append('normalize_phone', $('#opt_normalize_phone') && $('#opt_normalize_phone').checked ? '1' : '');
        fd.append('normalize_state', $('#opt_normalize_state') && $('#opt_normalize_state').checked ? '1' : '');
        fd.append('generate_password', $('#opt_generate_password') && $('#opt_generate_password').checked ? '1' : '');
        fd.append('check_tax_id', $('#opt_check_tax_id') && $('#opt_check_tax_id').checked ? '1' : '');
        fd.append('fill_required_custom_fields', $('#opt_fill_required_custom') && $('#opt_fill_required_custom').checked ? '1' : '');
        fd.append('sync_tax_id_custom_field', $('#opt_sync_tax_custom') && $('#opt_sync_tax_custom').checked ? '1' : '');
        fd.append('allow_missing_tax_id', $('#opt_allow_missing_tax') && $('#opt_allow_missing_tax').checked ? '1' : '');
        fd.append('generate_missing_tax_id', $('#opt_generate_missing_tax') && $('#opt_generate_missing_tax').checked ? '1' : '');
        fd.append('default_phone', $('#opt_default_phone') ? $('#opt_default_phone').value : '');
        fd.append('default_address1', $('#opt_default_address1') ? $('#opt_default_address1').value : '');
        fd.append('default_city', $('#opt_default_city') ? $('#opt_default_city').value : '');
        fd.append('default_state', $('#opt_default_state') ? $('#opt_default_state').value : '');
        fd.append('default_custom_field_text', $('#opt_default_custom_text') ? $('#opt_default_custom_text').value : '');

        var countryVal = $('#opt_default_country_val');
        var applyCountry = $('#opt_apply_default_country');
        fd.append('default_country', (applyCountry && applyCountry.checked && countryVal) ? countryVal.value : '');
        fd.append('default_currency', $('#opt_default_currency') ? $('#opt_default_currency').value : '');
        fd.append('default_groupid', $('#opt_default_groupid') ? $('#opt_default_groupid').value : '');
        fd.append('default_language', $('#opt_default_language') ? $('#opt_default_language').value : '');
        fd.append('tax_id_custom_field', $('#opt_tax_id_custom_field') ? $('#opt_tax_id_custom_field').value : '');

        ajaxPost(config.ajaxImport, fd, function (data) {
            if (!data.success) {
                showAlert(data.message || 'Erro ao salvar configurações.', 'error');
                return;
            }
            state.totalRows = data.total_rows;
            if (callback) callback(data);
        });
    }

    /* ── Step 5: Import ── */
    function showConfirmModal() {
        var modal = $('#cvixConfirmModal');
        var totalEl = $('#cvixConfirmTotal');
        if (totalEl) totalEl.textContent = state.totalRows;
        if (modal) modal.style.display = 'flex';
    }

    function hideConfirmModal() {
        var modal = $('#cvixConfirmModal');
        if (modal) modal.style.display = 'none';
    }

    function startImport() {
        hideConfirmModal();
        state.importing = true;

        var ready = $('#cvixImportReady');
        var progress = $('#cvixImportProgress');
        var complete = $('#cvixImportComplete');
        if (ready) ready.style.display = 'none';
        if (progress) progress.style.display = 'block';
        if (complete) complete.style.display = 'none';

        updateProgressUI(0, state.totalRows, { imported: 0, updated: 0, skipped: 0, errors: 0 });

        var fd = new FormData();
        fd.append('token', config.csrfToken);
        fd.append('action', 'start');
        fd.append('import_id', state.importId);

        ajaxPost(config.ajaxImport, fd, function (data) {
            if (!data.success) {
                showAlert(data.message || 'Erro ao iniciar importação.', 'error');
                state.importing = false;
                if (ready) ready.style.display = 'block';
                if (progress) progress.style.display = 'none';
                return;
            }
            processBatch();
        });
    }

    function processBatch() {
        if (!state.importing) return;

        var fd = new FormData();
        fd.append('token', config.csrfToken);
        fd.append('action', 'batch');
        fd.append('import_id', state.importId);

        ajaxPost(config.ajaxImport, fd, function (data) {
            if (!data.success) {
                showAlert(data.message || 'Erro durante importação.', 'error');
                state.importing = false;
                return;
            }

            updateProgressUI(data.offset, data.total, data.counters);
            showRecentErrors(data.recent_results);

            if (data.completed) {
                finishImport(data);
            } else {
                setTimeout(processBatch, 100);
            }
        });
    }

    function updateProgressUI(offset, total, counters) {
        var pct = total > 0 ? Math.round((offset / total) * 100) : 100;

        var currentEl = $('#cvixProgressCurrent');
        var totalEl = $('#cvixProgressTotal');
        var bar = $('#cvixProgressBar');
        var pctEl = $('#cvixProgressPercent');

        if (currentEl) currentEl.textContent = offset;
        if (totalEl) totalEl.textContent = total;
        if (bar) bar.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';

        setCounter('#cvixLiveImported', counters.imported);
        setCounter('#cvixLiveUpdated', counters.updated);
        setCounter('#cvixLiveSkipped', counters.skipped);
        setCounter('#cvixLiveErrors', counters.errors);
    }

    function setCounter(sel, val) {
        var el = $(sel);
        if (el) el.textContent = val;
    }

    function showRecentErrors(results) {
        if (!results || !results.length) return;
        var container = $('#cvixRecentErrors');
        if (!container) return;

        var errors = results.filter(function (r) { return r.status === 'error'; });
        if (!errors.length) return;

        var html = errors.map(function (r) {
            return '<div class="cvix-error-item">' +
                '<strong>Linha ' + r.row_number + '</strong> — ' +
                escapeHtml(r.email) + '<br>' +
                '<span class="cvix-text-danger">' + escapeHtml(r.message) + '</span></div>';
        }).join('');

        container.innerHTML = html + container.innerHTML;
    }

    function finishImport(data) {
        state.importing = false;

        var progress = $('#cvixImportProgress');
        var complete = $('#cvixImportComplete');
        if (progress) progress.style.display = 'none';
        if (complete) complete.style.display = 'block';

        setCounter('#cvixFinalTotal', data.total);
        setCounter('#cvixFinalImported', data.counters.imported);
        setCounter('#cvixFinalUpdated', data.counters.updated);
        setCounter('#cvixFinalSkipped', data.counters.skipped);
        setCounter('#cvixFinalErrors', data.counters.errors);

        var reportUrl = config.moduleLink + '&tab=report&import_id=' + state.importId;
        var downloadUrl = config.ajaxReport + '&report_action=download_csv&import_id=' + state.importId + '&token=' + encodeURIComponent(config.csrfToken);

        var viewBtn = $('#cvixViewReport');
        var dlBtn = $('#cvixDownloadReport');
        if (viewBtn) viewBtn.href = reportUrl;
        if (dlBtn) dlBtn.href = downloadUrl;
    }

    /* ── Navigation buttons ── */
    function initNavigation() {
        bindClick('#cvixBackToUpload', function () { setStep(1); });
        bindClick('#cvixToMapping', function () { setStep(3); });
        bindClick('#cvixBackToPreview', function () { setStep(2); });
        bindClick('#cvixToSettings', function () {
            var mapping = collectMapping();
            if (!mappingHasEmail(mapping)) {
                showAlert('Mapeie ao menos uma coluna para o campo E-mail (email) antes de continuar.', 'error');
                return;
            }
            persistWizardState();
            setStep(4);
        });
        bindClick('#cvixBackToMapping', function () { setStep(3); });

        bindClick('#cvixSaveAndContinue', function () {
            saveMappingAndSettings(function () {
                var totalEl = $('#cvixImportTotal');
                if (totalEl) totalEl.textContent = state.totalRows;
                setStep(5);
            });
        });

        bindClick('#cvixStartImportBtn', function () {
            saveMappingAndSettings(function () {
                showConfirmModal();
            });
        });

        bindClick('#cvixConfirmCancel', hideConfirmModal);
        bindClick('#cvixConfirmStart', startImport);
        bindClick('#cvixAlertOk', hideAlert);

        var skipExisting = $('#opt_skip_existing');
        var updateExisting = $('#opt_update_existing');
        if (skipExisting && updateExisting) {
            updateExisting.addEventListener('change', function () {
                if (updateExisting.checked) skipExisting.checked = false;
            });
            skipExisting.addEventListener('change', function () {
                if (skipExisting.checked) updateExisting.checked = false;
            });
        }
    }

    function bindClick(sel, fn) {
        var el = $(sel);
        if (el) el.addEventListener('click', fn);
    }

    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function autoSelectTaxCustomField() {
        var select = $('#opt_tax_id_custom_field');
        if (!select || select.value) {
            return;
        }

        var fields = typeof CVIX_CUSTOM_FIELDS !== 'undefined' ? CVIX_CUSTOM_FIELDS : [];
        for (var i = 0; i < fields.length; i++) {
            var name = String(fields[i].name || '').toLowerCase();
            if (name.indexOf('cpf') !== -1 || name.indexOf('cnpj') !== -1 || name.indexOf('documento') !== -1) {
                select.value = String(fields[i].id);
                break;
            }
        }
    }

    /* ── Init ── */
    document.addEventListener('DOMContentLoaded', function () {
        if ($('#cvixImportWizard')) {
            restoreWizardState();
            autoSelectTaxCustomField();
            initUpload();
            initNavigation();
            if (state.importId && state.headers.length) {
                buildMappingGrid();
            }
        }
    });
})();
