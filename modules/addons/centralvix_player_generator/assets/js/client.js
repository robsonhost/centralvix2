(function () {
    'use strict';

    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
    function qsa(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

    var csrf = qs('#cvix-pg-csrf');
    var ajaxBase = qs('#cvix-pg-ajax');

    function post(action, data) {
        var fd = new FormData();
        fd.append('csrf_token', csrf ? csrf.value : '');
        fd.append('action', action);
        Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
        return fetch(ajaxBase.value.replace('cvix_player_ajax=', 'cvix_player_ajax=save'), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); });
    }

    qsa('.cvix-pg-dup').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Duplicar este player?')) return;
            post('duplicate', { player_id: btn.getAttribute('data-id') }).then(function (res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro ao duplicar.');
            });
        });
    });

    qsa('.cvix-pg-del').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('Excluir este player permanentemente?')) return;
            post('delete', { player_id: btn.getAttribute('data-id') }).then(function (res) {
                if (res.success) location.reload();
                else alert(res.message || 'Erro ao excluir.');
            });
        });
    });

    qsa('.cvix-pg-copy').forEach(function (el) {
        el.addEventListener('focus', function () { el.select(); });
    });

    var form = qs('#cvix-pg-builder-form');
    if (!form || !window.CVIX_PG_BUILDER) return;

    var previewBox = qs('#cvix-pg-preview');
    var saveBtn = qs('#cvix-pg-save');
    var saveMsg = qs('#cvix-pg-save-msg');
    var debounce;

    function buildPreview() {
        clearTimeout(debounce);
        debounce = setTimeout(function () {
            var fd = new FormData(form);
            fd.append('existing_logo', window.CVIX_PG_BUILDER.logoUrl || '');
            fd.append('existing_bg', window.CVIX_PG_BUILDER.bgUrl || '');
            fetch(window.CVIX_PG_BUILDER.previewUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (!res.success) return;
                    previewBox.innerHTML = res.html;
                    initPlayerWidget(previewBox);
                });
        }, 350);
    }

    function initPlayerWidget(root) {
        var player = qs('.cvix-player', root);
        if (!player) return;
        var audio = qs('.cvix-player__audio', player);
        var video = qs('.cvix-player__video', player);
        var playBtn = qs('.cvix-player__play', player);
        var vol = qs('.cvix-player__volume', player);
        var stream = player.getAttribute('data-stream');
        var type = player.getAttribute('data-type') || 'audio';

        if (type === 'hls' || type === 'video') {
            if (video) { video.hidden = false; video.src = stream; }
        } else if (audio) {
            audio.src = stream;
        }

        if (playBtn) {
            playBtn.addEventListener('click', function () {
                var media = (type === 'hls' || type === 'video') ? video : audio;
                if (!media) return;
                if (media.paused) {
                    media.play().catch(function () {});
                    playBtn.classList.add('is-playing');
                } else {
                    media.pause();
                    playBtn.classList.remove('is-playing');
                }
            });
        }
        if (vol && audio) {
            vol.addEventListener('input', function () { audio.volume = parseFloat(vol.value); });
        }
    }

    qsa('input,select,textarea', form).forEach(function (el) {
        el.addEventListener('input', buildPreview);
        el.addEventListener('change', buildPreview);
    });

    qs('[name=theme_id]', form).addEventListener('change', function (e) {
        var opt = e.target.selectedOptions[0];
        if (!opt || !opt.value) return;
        qs('[name=primary_color]', form).value = opt.getAttribute('data-primary') || '#1e4fd8';
        qs('[name=secondary_color]', form).value = opt.getAttribute('data-secondary') || '#00bcd4';
        qs('[name=theme_mode]', form).value = opt.getAttribute('data-mode') || 'dark';
        buildPreview();
    });

    buildPreview();

    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            saveMsg.textContent = 'Salvando...';
            var fd = new FormData(form);
            fetch(window.CVIX_PG_BUILDER.saveUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (res) {
                    if (res.success) {
                        saveMsg.textContent = 'Player salvo!';
                        if (res.id && !qs('[name=player_id]', form).value) {
                            window.location.href = window.location.pathname + '?m=centralvix_player_generator&action=builder&id=' + res.id;
                        }
                    } else {
                        saveMsg.textContent = res.message || 'Erro ao salvar.';
                    }
                });
        });
    }
})();
