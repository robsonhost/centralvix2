<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

add_hook('AfterCronJob', 1, function () {
    if (!class_exists('CentralVixUpdDownloadService')) {
        require_once __DIR__ . '/lib/Security.php';
        CentralVixUpdModule::autoload();
    }
    CentralVixUpdDownloadService::purgeExpiredTokens();
});
