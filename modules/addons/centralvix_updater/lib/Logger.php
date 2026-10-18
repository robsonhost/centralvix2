<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixUpdLogger
{
    public static function log($action, $message, array $context = [], $productId = 0, $versionId = 0)
    {
        $ip = CentralVixUpdSecurity::clientIp();
        $dir = CentralVixUpdModule::storagePath('logs');
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $line = sprintf(
            "[%s] %s P%s V%s %s %s\n",
            date('Y-m-d H:i:s'),
            CentralVixUpdSecurity::sanitizeText($action, 50),
            $productId ?: '-',
            $versionId ?: '-',
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($dir . '/updater.log', $line, FILE_APPEND | LOCK_EX);
    }

    public static function logDownload($versionId, $productId, $status, $message = '', array $extra = [])
    {
        Capsule::table('mod_centralvix_updater_downloads')->insert([
            'version_id' => (int) $versionId,
            'product_id' => (int) $productId,
            'token_hash' => $extra['token_hash'] ?? null,
            'license_key_hash' => $extra['license_key_hash'] ?? null,
            'ip_address' => CentralVixUpdSecurity::clientIp(),
            'status' => CentralVixUpdSecurity::sanitizeText($status, 30),
            'message' => CentralVixUpdSecurity::sanitizeText($message, 500),
            'context_json' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function recentDownloads($limit = 100)
    {
        return Capsule::table('mod_centralvix_updater_downloads as d')
            ->leftJoin('mod_centralvix_updater_versions as v', 'v.id', '=', 'd.version_id')
            ->leftJoin('mod_centralvix_updater_products as p', 'p.id', '=', 'd.product_id')
            ->select('d.*', 'v.version', 'p.name as product_name', 'p.slug as product_slug')
            ->orderBy('d.id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function tailLog($lines = 80)
    {
        $file = CentralVixUpdModule::storagePath('logs/updater.log');
        if (!is_file($file)) {
            return [];
        }
        $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$content) {
            return [];
        }
        return array_slice($content, -$lines);
    }
}
