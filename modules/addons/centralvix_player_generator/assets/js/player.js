(function () {
    'use strict';

    function qs(sel, ctx) { return (ctx || document).querySelector(sel); }

    var player = qs('.cvix-player');
    if (!player) return;

    var audio = qs('.cvix-player__audio', player);
    var video = qs('.cvix-player__video', player);
    var playBtn = qs('.cvix-player__play', player);
    var vol = qs('.cvix-player__volume', player);
    var trackEl = qs('.cvix-player__track', player);
    var artistEl = qs('.cvix-player__artist', player);
    var listenersEl = qs('.cvix-player__listeners-n', player);
    var coverImg = qs('.cvix-player__cover-img', player);

    var stream = player.getAttribute('data-stream');
    var type = player.getAttribute('data-type') || 'audio';
    var hlsInstance = null;

    function getMedia() {
        if (type === 'hls' || type === 'video') return video;
        return audio;
    }

    function setupStream() {
        if (type === 'hls') {
            if (window.Hls && window.Hls.isSupported()) {
                hlsInstance = new window.Hls();
                hlsInstance.loadSource(stream);
                hlsInstance.attachMedia(video);
                video.hidden = false;
            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                video.src = stream;
                video.hidden = false;
            }
            return;
        }
        if (type === 'video') {
            video.src = stream;
            video.hidden = false;
            return;
        }
        audio.src = stream;
    }

    setupStream();

    if (playBtn) {
        playBtn.addEventListener('click', function () {
            var media = getMedia();
            if (!media) return;
            if (media.paused) {
                media.play().then(function () {
                    playBtn.classList.add('is-playing');
                    playBtn.querySelector('.cvix-player__play-icon').textContent = '❚❚';
                }).catch(function () {
                    media.muted = true;
                    media.play().then(function () {
                        playBtn.classList.add('is-playing');
                        if (vol) vol.value = 0;
                    }).catch(function () {});
                });
            } else {
                media.pause();
                playBtn.classList.remove('is-playing');
                playBtn.querySelector('.cvix-player__play-icon').textContent = '▶';
            }
        });
    }

    if (vol) {
        var media = getMedia();
        if (media) {
            vol.addEventListener('input', function () {
                media.volume = parseFloat(vol.value);
                media.muted = parseFloat(vol.value) === 0;
            });
            media.volume = parseFloat(vol.value);
        }
    }

    function applyMeta(meta) {
        if (!meta) return;
        if (trackEl && meta.title) trackEl.textContent = meta.title;
        if (artistEl && meta.artist) artistEl.textContent = meta.artist;
        if (listenersEl) listenersEl.textContent = meta.listeners || 0;
        if (coverImg && meta.cover) {
            coverImg.src = meta.cover;
            coverImg.hidden = false;
        }
    }

    function fetchMeta() {
        if (!window.CVIX_PLAYER || !window.CVIX_PLAYER.metaUrl) return;
        fetch(window.CVIX_PLAYER.metaUrl, { credentials: 'omit' })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.meta) applyMeta(res.meta);
            })
            .catch(function () {});
    }

    fetchMeta();
    setInterval(fetchMeta, (window.CVIX_PLAYER && window.CVIX_PLAYER.pollInterval) || 15000);
})();
