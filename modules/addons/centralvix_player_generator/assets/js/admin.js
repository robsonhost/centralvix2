(function () {
    'use strict';
    document.querySelectorAll('.cvix-pg-copy-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.cvix-pg-player-card');
            var ta = card ? card.querySelector('.cvix-pg-iframe-code') : null;
            if (!ta) return;
            ta.select();
            ta.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(ta.value).then(function () {
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar iframe'; }, 2000);
            });
        });
    });
})();
