(function () {
    var btn = document.getElementById('cvix-copy-key');
    var input = document.getElementById('cvix-license-key');
    if (btn && input) {
        btn.addEventListener('click', function () {
            input.select();
            navigator.clipboard.writeText(input.value).then(function () {
                btn.textContent = 'Copiado!';
                setTimeout(function () { btn.textContent = 'Copiar chave'; }, 2000);
            });
        });
    }
})();
