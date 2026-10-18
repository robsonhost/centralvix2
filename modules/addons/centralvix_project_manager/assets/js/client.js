(function () {
    'use strict';

    function post(url, fd) {
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
    }

    var c = window.CVIX_PM_CLIENT || {};
    var commentBtn = document.querySelector('.cvix-pm-client-comment-btn');
    if (commentBtn && c.comment) {
        commentBtn.addEventListener('click', function () {
            post(c.comment, new FormData(document.getElementById('cvix-pm-client-comment'))).then(function (res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro');
            });
        });
    }

    var uploadBtn = document.querySelector('.cvix-pm-client-upload-btn');
    if (uploadBtn && c.upload) {
        uploadBtn.addEventListener('click', function () {
            post(c.upload, new FormData(document.getElementById('cvix-pm-client-upload'))).then(function (res) {
                alert(res.success ? 'Enviado.' : (res.message || 'Erro'));
                if (res.success) location.reload();
            });
        });
    }
})();
