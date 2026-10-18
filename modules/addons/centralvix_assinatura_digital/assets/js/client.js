(function () {
    'use strict';

    var cfg = window.CVIX_SIGN || {};
    var canvas = document.getElementById('cvix-signature-pad');
    var drawing = false;
    var ctx = canvas ? canvas.getContext('2d') : null;

    if (canvas && ctx) {
        ctx.strokeStyle = '#0a1628';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        function pos(e) {
            var r = canvas.getBoundingClientRect();
            var t = e.touches ? e.touches[0] : e;
            return { x: t.clientX - r.left, y: t.clientY - r.top };
        }
        function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function move(e) {
            if (!drawing) return;
            var p = pos(e);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            e.preventDefault();
        }
        function end() { drawing = false; }
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);
        var clearBtn = document.getElementById('cvix-clear-sig');
        if (clearBtn) clearBtn.addEventListener('click', function () {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        });
    }

    function post(url, data) {
        var fd = new FormData();
        Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    var signBtn = document.getElementById('cvix-sign-btn');
    if (signBtn) {
        signBtn.addEventListener('click', function () {
            var accept = document.getElementById('cvix-accept-terms');
            var msg = document.getElementById('cvix-sign-msg');
            if (!accept || !accept.checked) {
                alert('Marque que leu e concorda com o documento.');
                return;
            }
            var payload = {
                document_id: cfg.documentId,
                accept_terms: '1',
                signer_document: (document.getElementById('cvix-signer-doc') || {}).value || ''
            };
            if (cfg.publicMode && cfg.accessToken) {
                payload.access_token = cfg.accessToken;
            } else {
                payload.csrf_token = (document.getElementById('cvix-csrf') || {}).value || '';
            }
            if (canvas) {
                try { payload.signature_data = canvas.toDataURL('image/png'); } catch (e) {}
            }
            if (msg) msg.textContent = 'Processando...';
            post(cfg.signUrl, payload).then(function (res) {
                if (msg) msg.textContent = res.success ? 'Documento assinado. ID: ' + (res.transaction_id || '') : (res.message || 'Erro');
                if (res.success) setTimeout(function () {
                    window.location.href = cfg.listUrl || '/';
                }, 2000);
            });
        });
    }

    var rejectBtn = document.getElementById('cvix-reject-btn');
    if (rejectBtn) {
        rejectBtn.addEventListener('click', function () {
            var reason = prompt('Motivo da recusa:');
            if (!reason) return;
            var payload = { document_id: cfg.documentId, reason: reason };
            if (cfg.publicMode && cfg.accessToken) {
                payload.access_token = cfg.accessToken;
            } else {
                payload.csrf_token = (document.getElementById('cvix-csrf') || {}).value || '';
            }
            post(cfg.rejectUrl, payload).then(function (res) {
                alert(res.success ? 'Recusa registrada.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }
})();
