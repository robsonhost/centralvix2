<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

add_hook('ClientAreaPage', 1, function ($vars) {
    $file = ROOTDIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'centralvix-public-config.php';
    $cvix = is_file($file) ? include $file : [];
    return ['cvix' => is_array($cvix) ? $cvix : []];
});
