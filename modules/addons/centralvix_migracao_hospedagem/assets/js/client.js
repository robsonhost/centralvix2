(function () {
    var credToggle = document.getElementById('cvix-provide-cred');
    var credFields = document.getElementById('cvix-cred-fields');
    if (credToggle && credFields) {
        credToggle.addEventListener('change', function () {
            credFields.style.display = this.checked ? 'block' : 'none';
        });
    }

    function postForm(form, done) {
        var fd = new FormData(form);
        fetch(form.getAttribute('action') || form.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(done).catch(function () {
            done({ success: false, message: 'Erro de comunicação.' });
        });
    }

    var createForm = document.getElementById('cvix-mig-create-form');
    if (createForm) {
        createForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = document.getElementById('cvix-mig-form-msg');
            postForm(createForm, function (res) {
                if (msg) {
                    msg.className = res.success ? 'cvix-mig-alert ok' : 'cvix-mig-alert err';
                    msg.textContent = res.success
                        ? 'Solicitação enviada! Ref: ' + (res.reference || '') + '. Redirecionando...'
                        : (res.message || 'Erro ao enviar.');
                }
                if (res.success && res.id) {
                    setTimeout(function () {
                        window.location.href = createForm.getAttribute('data-back') || window.location.pathname + '?m=centralvix_migracao_hospedagem&action=request&id=' + res.id;
                    }, 1200);
                }
            });
        });
    }

    var commentForm = document.getElementById('cvix-mig-comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            postForm(commentForm, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Erro');
                }
            });
        });
    }

    var uploadForm = document.getElementById('cvix-mig-upload-form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function (e) {
            e.preventDefault();
            postForm(uploadForm, function (res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Erro no upload');
                }
            });
        });
    }
})();
