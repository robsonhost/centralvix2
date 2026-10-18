<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixLicLogger
{
    public static function log($licenseId, $action, $message, array $context = [], $clientId = 0)
    {
        Capsule::table('mod_centralvix_license_logs')->insert([
            'license_id' => $licenseId ? (int) $licenseId : null,
            'client_id' => (int) $clientId,
            'action' => CentralVixLicSecurity::sanitizeText($action, 50),
            'message' => CentralVixLicSecurity::sanitizeText($message, 2000),
            'ip_address' => CentralVixLicSecurity::clientIp(),
            'context_json' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $dir = CentralVixLicModule::path() . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/license.log',
            sprintf("[%s] L%s %s %s\n", date('Y-m-d H:i:s'), $licenseId ?: '-', $action, $message),
            FILE_APPEND | LOCK_EX
        );
    }

    public static function listRecent($limit = 200)
    {
        return Capsule::table('mod_centralvix_license_logs as l')
            ->leftJoin('mod_centralvix_licenses as lic', 'lic.id', '=', 'l.license_id')
            ->select('l.*', 'lic.license_key')
            ->orderBy('l.id', 'desc')
            ->limit($limit)
            ->get();
    }
}
