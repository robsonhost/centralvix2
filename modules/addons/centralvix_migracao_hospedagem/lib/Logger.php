<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigLogger
{
    public static function log($requestId, $action, $message, array $context = [], $actorType = 'system', $actorId = 0)
    {
        $message = CentralVixMigSecurity::redactSecrets(CentralVixMigSecurity::sanitizeText($message, 2000));
        $context = self::redactContext($context);

        Capsule::table('mod_centralvix_migration_logs')->insert([
            'request_id' => (int) $requestId,
            'action' => CentralVixMigSecurity::sanitizeText($action, 50),
            'message' => $message,
            'actor_type' => CentralVixMigSecurity::sanitizeText($actorType, 20),
            'actor_id' => (int) $actorId,
            'ip_address' => CentralVixMigSecurity::clientIp(),
            'context_json' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $dir = CentralVixMigModule::storagePath('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        @file_put_contents(
            $dir . '/migration.log',
            sprintf("[%s] R%s %s %s\n", date('Y-m-d H:i:s'), $requestId ?: '-', $action, $message),
            FILE_APPEND | LOCK_EX
        );
    }

    public static function redactContext(array $context)
    {
        $forbidden = ['password', 'senha', 'credentials', 'credentials_blob', 'ftp_pass', 'cpanel_pass'];
        foreach ($forbidden as $key) {
            if (isset($context[$key])) {
                $context[$key] = '[REDACTED]';
            }
        }
        return $context;
    }

    public static function forRequest($requestId, $limit = 100)
    {
        return Capsule::table('mod_centralvix_migration_logs')
            ->where('request_id', (int) $requestId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
