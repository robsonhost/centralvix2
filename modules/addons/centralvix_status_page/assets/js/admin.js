(function () {
    'use strict';

    var cfg = window.CVIX_STATUS || {};

    function toast(msg, ok) {
        var el = document.createElement('div');
        el.className = 'cvix-sp-toast ' + (ok ? 'ok' : 'err');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 3500);
    }

    function post(url, data, cb) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) {
            if (Array.isArray(data[k])) {
                data[k].forEach(function (v) { fd.append(k + '[]', v); });
            } else {
                fd.append(k, data[k]);
            }
        });
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) { if (cb) cb(j); })
            .catch(function () { toast('Erro de comunicação com o servidor.', false); });
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

    function bindForm(id, url, msg) {
        var form = document.getElementById(id);
        if (!form) return;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            post(url, formData(form), function (j) {
                toast(j.message || msg, j.success);
                if (j.success) setTimeout(function () { location.reload(); }, 800);
            });
        });
    }

    bindForm('cvixServiceForm', cfg.ajaxSaveService, 'Serviço salvo.');
    bindForm('cvixIncidentForm', cfg.ajaxSaveIncident, 'Incidente salvo.');
    bindForm('cvixMaintenanceForm', cfg.ajaxSaveMaintenance, 'Manutenção salva.');

    document.querySelectorAll('.cvix-check-now').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            btn.disabled = true;
            post(cfg.ajaxCheck, { token: cfg.csrf, service_id: id }, function (j) {
                btn.disabled = false;
                if (j.success && j.result) {
                    toast(j.result.message + ' (' + j.result.response_time + 'ms)', j.result.status === 'operational');
                    setTimeout(function () { location.reload(); }, 1000);
                } else {
                    toast(j.message || 'Falha na checagem.', false);
                }
            });
        });
    });

    var runAll = document.getElementById('cvixRunAllChecks');
    if (runAll) {
        runAll.addEventListener('click', function () {
            runAll.disabled = true;
            runAll.textContent = 'Executando...';
            post(cfg.ajaxRunAll, { token: cfg.csrf }, function (j) {
                runAll.disabled = false;
                runAll.textContent = 'Executar todos os monitores agora';
                if (j.success) {
                    toast('Checados ' + j.checked + ' serviços.', true);
                    var box = document.getElementById('cvixMonitorResults');
                    if (box && j.results) {
                        box.innerHTML = j.results.map(function (r) {
                            return '<div>' + r.status + ' — #' + r.service_id + ': ' + (r.message || '') + '</div>';
                        }).join('');
                    }
                    setTimeout(function () { location.reload(); }, 1500);
                } else {
                    toast(j.message || 'Erro.', false);
                }
            });
        });
    }

    function drawChart(canvasId, rows) {
        var canvas = document.getElementById(canvasId);
        if (!canvas || !rows || !rows.length) return;
        var ctx = canvas.getContext('2d');
        var w = canvas.parentElement.clientWidth - 32;
        var h = 100;
        canvas.width = w;
        canvas.height = h;
        ctx.clearRect(0, 0, w, h);
        var barW = Math.max(4, w / rows.length - 2);
        rows.forEach(function (row, i) {
            var pct = row.total > 0 ? (row.ok_count / row.total) : 1;
            var barH = pct * (h - 20);
            var x = i * (barW + 2) + 4;
            ctx.fillStyle = pct >= 0.99 ? '#10b981' : (pct >= 0.9 ? '#f59e0b' : '#ef4444');
            ctx.fillRect(x, h - barH - 4, barW, barH);
        });
    }

    if (window.CVIX_CHART_24) drawChart('cvixChart24', window.CVIX_CHART_24);
    if (window.CVIX_CHART_7) drawChart('cvixChart7', window.CVIX_CHART_7);
    if (window.CVIX_CHART_30) drawChart('cvixChart30', window.CVIX_CHART_30);
})();
