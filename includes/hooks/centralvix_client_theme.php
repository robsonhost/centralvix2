<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

/**
 * CentralVix Client Theme — WhatsApp opcional.
 * CSS/JS carregados via includes/head.tpl e footer.tpl (evita duplicar assets).
 */
add_hook('ClientAreaFooterOutput', 1, function ($vars) {
    if (($vars['template'] ?? '') !== 'centralvix-client') {
        return '';
    }

    $html = '';

    if (defined('CENTRALVIX_CLIENT_WHATSAPP') && CENTRALVIX_CLIENT_WHATSAPP !== '') {
        $whatsapp = preg_replace('/\D/', '', (string) CENTRALVIX_CLIENT_WHATSAPP);
        if ($whatsapp !== '') {
            $html .= '<script>document.addEventListener("DOMContentLoaded",function(){var w=document.getElementById("cv-whatsapp-wrap");if(w){w.setAttribute("data-whatsapp","' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '");}});</script>';
        }
    }

    return $html;
});
