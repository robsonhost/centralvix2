<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJLogger
{
    public static function log($action, $result, $accountId = 0, $clientId = 0, $details = '')
    {
        return (int) Capsule::table('mod_centralvix_autodj_logs')->insertGetId([
            'account_id' => (int) $accountId,
            'client_id' => (int) ($clientId ?: ($_SESSION['uid'] ?? 0)),
            'admin_id' => (int) ($_SESSION['adminid'] ?? 0),
            'action' => substr($action, 0, 100),
            'result' => substr($result, 0, 50),
            'details' => CentralVixAutoDJSecurity::sanitizeLog($details),
            'ip_address' => substr(CentralVixAutoDJSecurity::clientIp(), 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function listLogs($limit = 100, $accountId = 0)
    {
        $q = Capsule::table('mod_centralvix_autodj_logs')->orderBy('id', 'desc')->limit($limit);
        if ($accountId) {
            $q->where('account_id', (int) $accountId);
        }
        return $q->get();
    }
}
