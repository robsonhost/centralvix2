(function () {
    'use strict';

    function post(action, form) {
        var fd = new FormData(form);
        var url = (window.CVIX_SB_AJAX || '').replace('cvix_site_ajax=', 'cvix_site_ajax=' + action);
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    var statusBtn = document.querySelector('.cvix-sb-change-status');
    if (statusBtn) {
        statusBtn.addEventListener('click', function () {
            var form = document.getElementById('cvix-sb-status-form');
            post('change_status', form).then(function (res) {
                alert(res.success ? 'Status atualizado.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }

    var uploadBtn = document.querySelector('.cvix-sb-upload-btn');
    if (uploadBtn) {
        uploadBtn.addEventListener('click', function () {
            var form = document.getElementById('cvix-sb-admin-upload');
            var fd = new FormData(form);
            var url = (window.CVIX_SB_AJAX || '').replace('cvix_site_ajax=', 'cvix_site_ajax=upload');
            fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    alert(res.success ? 'Arquivo enviado.' : (res.message || 'Erro'));
                    if (res.success) location.reload();
                });
        });
    }
})();
