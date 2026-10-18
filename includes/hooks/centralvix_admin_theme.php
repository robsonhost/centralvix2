<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

/**
 * CentralVix Admin Theme — fallback de assets via hooks oficiais.
 * Referência: https://developers.whmcs.com/hooks-reference/admin-area/
 */
add_hook('AdminAreaHeadOutput', 1, function ($vars) {
    if (($vars['template'] ?? '') !== 'centralvixadmin') {
        return '';
    }

    $version = '1.0.0';
    $href = 'templates/centralvixadmin/assets/css/centralvix-admin.min.css?v=' . rawurlencode($version);

    return '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
});

add_hook('AdminAreaFooterOutput', 1, function ($vars) {
    if (($vars['template'] ?? '') !== 'centralvixadmin') {
        return '';
    }

    $version = '1.0.0';
    $src = 'templates/centralvixadmin/assets/js/centralvix-admin.min.js?v=' . rawurlencode($version);

    return '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '"></script>';
});
