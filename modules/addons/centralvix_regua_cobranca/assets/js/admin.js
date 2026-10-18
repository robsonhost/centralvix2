(function () {
    'use strict';

    var cfg = window.CVIX_REGUA || {};

    function toast(msg, ok) {
        var el = document.createElement('div');
        el.className = 'cvix-rc-toast ' + (ok ? 'ok' : 'err');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 4000);
    }

    function post(url, data, cb) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) {
            if (Array.isArray(data[k])) {
                data[k].forEach(function (v) { fd.append(k + '[]', v); });
            } else if (data[k] !== undefined && data[k] !== null) {
                fd.append(k, data[k]);
            }
        });
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) { if (cb) cb(j); })
            .catch(function () { toast('Erro de comunicação.', false); });
    }

    function formData(form) {
        var data = { token: cfg.csrf };
        form.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (!el.name || el.type === 'submit') return;
            if (el.type === 'checkbox') {
                if (el.checked) data[el.name] = el.value || '1';
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    var ruleForm = document.getElementById('cvixRuleForm');
    if (ruleForm) {
        ruleForm.addEventListener('submit', function (e) {
            e.preventDefault();
            post(cfg.ajaxSaveRule, formData(ruleForm), function (j) {
                toast(j.message || (j.success ? 'Salvo.' : 'Erro.'), j.success);
                if (j.success) setTimeout(function () { location.href = cfg.moduleLink + '&tab=rules'; }, 700);
            });
        });
    }

    document.querySelectorAll('.cvix-toggle-rule').forEach(function (btn) {
        btn.addEventListener('click', function () {
            post(cfg.ajaxToggleRule, { token: cfg.csrf, id: btn.getAttribute('data-id') }, function (j) {
                toast(j.message || '', j.success);
                if (j.success) location.reload();
            });
        });
    });

    var modal = document.getElementById('cvixConfirmModal');
    var confirmText = document.getElementById('cvixConfirmText');
    var confirmOk = document.getElementById('cvixConfirmOk');
    var confirmCancel = document.getElementById('cvixConfirmCancel');
    var pendingDeleteId = null;

    document.querySelectorAll('.cvix-delete-rule').forEach(function (btn) {
        btn.addEventListener('click', function () {
            pendingDeleteId = btn.getAttribute('data-id');
            if (confirmText) confirmText.textContent = 'Excluir a régua "' + btn.getAttribute('data-name') + '"?';
            if (modal) modal.classList.remove('hidden');
        });
    });

    if (confirmCancel) {
        confirmCancel.addEventListener('click', function () {
            pendingDeleteId = null;
            if (modal) modal.classList.add('hidden');
        });
    }

    if (confirmOk) {
        confirmOk.addEventListener('click', function () {
            if (!pendingDeleteId) return;
            post(cfg.ajaxDeleteRule, { token: cfg.csrf, id: pendingDeleteId }, function (j) {
                if (modal) modal.classList.add('hidden');
                toast(j.message || '', j.success);
                if (j.success) location.reload();
            });
        });
    }

    var previewBtn = document.getElementById('cvixPreviewBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function () {
            var form = previewBtn.closest('form');
            var data = {
                token: cfg.csrf,
                subject: form.querySelector('[name=subject]').value,
                body_email: form.querySelector('[name=body_email]').value,
                body_whatsapp: form.querySelector('[name=body_whatsapp]').value,
                invoice_id: document.getElementById('cvixPreviewInvoiceId').value || 0
            };
            post(cfg.ajaxPreview, data, function (j) {
                if (!j.success || !j.preview) {
                    toast(j.message || 'Erro no preview.', false);
                    return;
                }
                document.getElementById('cvixPreviewBox').classList.remove('hidden');
                document.getElementById('cvixPreviewSubject').textContent = j.preview.subject;
                document.getElementById('cvixPreviewEmail').innerHTML = j.preview.body_email;
                document.getElementById('cvixPreviewWhatsapp').textContent = j.preview.body_whatsapp;
            });
        });
    }

    var testForm = document.getElementById('cvixTestWhatsappForm');
    if (testForm) {
        testForm.addEventListener('submit', function (e) {
            e.preventDefault();
            post(cfg.ajaxTestWhatsapp, formData(testForm), function (j) {
                var box = document.getElementById('cvixTestResult');
                if (box) box.innerHTML = '<p class="cvix-rc-badge ' + (j.success ? 'ok' : 'danger') + '">' + (j.message || '') + '</p>';
                toast(j.message || '', j.success);
            });
        });
    }
})();
