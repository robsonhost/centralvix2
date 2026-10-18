(function () {
    'use strict';
    var interval = 60000;
    setInterval(function () {
        if (document.hidden) return;
        window.location.reload();
    }, interval);
})();
