<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStreamingLogger
{
    public static function log($action, $result, $accountId = 0, $details = '', $clientId = 0)
    {
        return (int) Capsule::table('mod_centralvix_stream_logs')->insertGetId([
            'account_id' => (int) $accountId,
            'admin_id' => (int) ($_SESSION['adminid'] ?? 0),
            'client_id' => (int) ($clientId ?: ($_SESSION['uid'] ?? 0)),
            'action' => substr($action, 0, 100),
            'result' => substr($result, 0, 50),
            'details' => CentralVixStreamingSecurity::sanitizeLog($details),
            'ip_address' => substr(CentralVixStreamingSecurity::clientIp(), 0, 45),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function listLogs($limit = 100, $accountId = 0)
    {
        $q = Capsule::table('mod_centralvix_stream_logs')->orderBy('id', 'desc')->limit($limit);
        if ($accountId) {
            $q->where('account_id', (int) $accountId);
        }
        return $q->get();
    }
}
