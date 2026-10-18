<?php

if (!defined('WHMCS')) {
    die('This hook should not be run directly');
}

$bust = static function (): void {
    $helper = ROOTDIR . '/includes/centralvix-whmcs-catalog.php';
    if (is_file($helper)) {
        require_once $helper;
        if (function_exists('centralvix_catalog_bust_cache')) {
            centralvix_catalog_bust_cache();
        }
    }
};

foreach (['ProductAdd', 'ProductEdit', 'ProductDelete', 'AfterConfigOptionsUpgrade'] as $hook) {
    add_hook($hook, 1, $bust);
}

add_hook('DailyCronJob', 1, $bust);
