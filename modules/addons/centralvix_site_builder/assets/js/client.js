(function () {
    'use strict';

    function post(url, fd) {
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    document.querySelectorAll('.cvix-sb-save-briefing').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('cvix-sb-briefing-form');
            if (!form || !window.CVIX_SB_BRIEFING_URL) return;
            var fd = new FormData(form);
            if (btn.getAttribute('data-submit') === '1') fd.append('submit', '1');
            var msg = document.getElementById('cvix-sb-briefing-msg');
            if (msg) msg.textContent = 'Salvando...';
            post(window.CVIX_SB_BRIEFING_URL, fd).then(function (res) {
                if (msg) msg.textContent = res.success ? 'Salvo com sucesso!' : (res.message || 'Erro');
                if (res.success && btn.getAttribute('data-submit') === '1') setTimeout(function () { location.reload(); }, 800);
            });
        });
    });

    var uploadBtn = document.querySelector('.cvix-sb-upload-client');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            var form = document.getElementById('cvix-sb-upload-form');
            if (!form || !window.CVIX_SB_UPLOAD) return;
            post(window.CVIX_SB_UPLOAD, new FormData(form)).then(function (res) {
                alert(res.success ? 'Upload concluído.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }

    document.querySelectorAll('.cvix-sb-add-comment').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = btn.closest('.cvix-sb-comment-form');
            if (!form || !window.CVIX_SB_COMMENT) return;
            post(window.CVIX_SB_COMMENT, new FormData(form)).then(function (res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro');
            });
        });
    });

    document.querySelectorAll('.cvix-sb-approve').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var form = document.getElementById('cvix-sb-approval-form');
            if (!form || !window.CVIX_SB_APPROVE) return;
            var action = btn.getAttribute('data-action');
            var fd = new FormData(form);
            fd.append('approval_action', action);
            if (action === 'request_change' && !fd.get('message')) {
                alert('Descreva as alterações desejadas.');
                return;
            }
            var msg = document.getElementById('cvix-sb-approval-msg');
            if (msg) msg.textContent = 'Enviando...';
            post(window.CVIX_SB_APPROVE, fd).then(function (res) {
                if (msg) msg.textContent = res.success ? 'Registrado!' : (res.message || 'Erro');
                if (res.success) setTimeout(function () { location.reload(); }, 800);
            });
        });
    });
})();
