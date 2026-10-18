<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/Security.php';
CentralVixLicModule::autoload();

add_hook('AfterModuleCreate', 1, function ($vars) {
    if (CentralVixLicModule::setting('auto_provision', '') !== 'on') {
        return;
    }
    require_once __DIR__ . '/lib/Security.php';
    CentralVixLicModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? 0);
    if ($serviceId) {
        CentralVixLicLicenseService::createFromService($serviceId);
    }
});

add_hook('AfterModuleSuspend', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixLicModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? 0);
    $lic = WHMCS\Database\Capsule::table('mod_centralvix_licenses')->where('service_id', $serviceId)->first();
    if ($lic) {
        CentralVixLicLicenseService::setStatus($lic->id, 'suspensa', 'Suspensa via WHMCS AfterModuleSuspend');
    }
});

add_hook('AfterModuleUnsuspend', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixLicModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? 0);
    $lic = WHMCS\Database\Capsule::table('mod_centralvix_licenses')->where('service_id', $serviceId)->first();
    if ($lic) {
        CentralVixLicLicenseService::setStatus($lic->id, 'ativa', 'Reativada via WHMCS AfterModuleUnsuspend');
    }
});

add_hook('AfterModuleTerminate', 1, function ($vars) {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixLicModule::autoload();
    $serviceId = (int) ($vars['params']['serviceid'] ?? 0);
    $lic = WHMCS\Database\Capsule::table('mod_centralvix_licenses')->where('service_id', $serviceId)->first();
    if ($lic) {
        CentralVixLicLicenseService::setStatus($lic->id, 'cancelada', 'Cancelada via WHMCS AfterModuleTerminate');
    }
});

add_hook('AfterCronJob', 1, function () {
    require_once __DIR__ . '/lib/Security.php';
    CentralVixLicModule::autoload();
    CentralVixLicLicenseService::checkExpired();
});
