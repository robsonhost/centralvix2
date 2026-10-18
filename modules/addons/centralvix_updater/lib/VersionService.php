<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixUpdVersionService
{
    public static function listByProduct($productId, $channel = null)
    {
        $q = Capsule::table('mod_centralvix_updater_versions')
            ->where('product_id', (int) $productId)
            ->orderBy('released_at', 'desc');
        if ($channel) {
            $q->where('channel', CentralVixUpdSecurity::validateChannel($channel));
        }
        return $q->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_updater_versions')->where('id', (int) $id)->first();
    }

    public static function latestForProduct($productId, $channel = 'stable')
    {
        return Capsule::table('mod_centralvix_updater_versions')
            ->where('product_id', (int) $productId)
            ->where('channel', CentralVixUpdSecurity::validateChannel($channel))
            ->where('active', 1)
            ->orderBy('released_at', 'desc')
            ->get()
            ->sort(function ($a, $b) {
                return version_compare($b->version, $a->version);
            })
            ->first();
    }

    public static function compareVersions($a, $b)
    {
        return version_compare($a, $b);
    }

    public static function saveMeta(array $data, $id = 0)
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $product = CentralVixUpdProductService::get($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Produto inválido.'];
        }

        $version = CentralVixUpdSecurity::sanitizeVersion($data['version'] ?? '');
        if ($version === '') {
            return ['success' => false, 'message' => 'Versão inválida.'];
        }

        $channel = CentralVixUpdSecurity::validateChannel($data['channel'] ?? 'stable');
        $minVersion = CentralVixUpdSecurity::sanitizeVersion($data['min_version'] ?? '0.0.0') ?: '0.0.0';
        $minPhp = CentralVixUpdSecurity::sanitizeText($data['min_php'] ?? '7.4', 20);
        $minWhmcs = CentralVixUpdSecurity::sanitizeText($data['min_whmcs'] ?? '', 20);

        $row = [
            'product_id' => $productId,
            'version' => $version,
            'channel' => $channel,
            'release_notes' => trim(strip_tags((string) ($data['release_notes'] ?? ''), '<p><br><ul><ol><li><strong><em>')),
            'min_version' => $minVersion,
            'min_php' => $minPhp,
            'min_whmcs' => $minWhmcs !== '' ? $minWhmcs : null,
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $existing = self::get($id);
            if (!$existing) {
                return ['success' => false, 'message' => 'Versão não encontrada.'];
            }
            Capsule::table('mod_centralvix_updater_versions')->where('id', $id)->update($row);
            CentralVixUpdLogger::log('version_update', 'Versão ' . $version . ' atualizada', [], $productId, $id);
            return ['success' => true, 'id' => $id];
        }

        $dup = Capsule::table('mod_centralvix_updater_versions')
            ->where('product_id', $productId)
            ->where('version', $version)
            ->where('channel', $channel)
            ->first();
        if ($dup) {
            return ['success' => false, 'message' => 'Versão/canal já cadastrados.'];
        }

        $row['file_path'] = '';
        $row['file_size'] = 0;
        $row['sha256_hash'] = '';
        $row['signature'] = '';
        $row['released_at'] = date('Y-m-d H:i:s');
        $row['created_at'] = date('Y-m-d H:i:s');
        $id = Capsule::table('mod_centralvix_updater_versions')->insertGetId($row);
        CentralVixUpdLogger::log('version_create', 'Versão ' . $version . ' criada', [], $productId, $id);
        return ['success' => true, 'id' => $id];
    }

    public static function attachPackage($versionId, $relativePath, $size, $sha256, $signature)
    {
        Capsule::table('mod_centralvix_updater_versions')->where('id', (int) $versionId)->update([
            'file_path' => $relativePath,
            'file_size' => (int) $size,
            'sha256_hash' => $sha256,
            'signature' => $signature,
            'released_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function resolveFilePath($version)
    {
        if (!$version || empty($version->file_path)) {
            return null;
        }
        $full = CentralVixUpdModule::storagePath($version->file_path);
        if (!CentralVixUpdSecurity::isPathInside($full, CentralVixUpdModule::storagePath('packages'))) {
            return null;
        }
        return is_file($full) ? $full : null;
    }

    public static function dashboardStats()
    {
        return [
            'products' => (int) Capsule::table('mod_centralvix_updater_products')->count(),
            'versions' => (int) Capsule::table('mod_centralvix_updater_versions')->count(),
            'downloads' => (int) Capsule::table('mod_centralvix_updater_downloads')
                ->where('status', 'downloaded')->count(),
            'updates' => (int) Capsule::table('mod_centralvix_updater_downloads')
                ->where('status', 'check_update')->count(),
            'failures' => (int) Capsule::table('mod_centralvix_updater_downloads')
                ->whereIn('status', ['failed', 'denied', 'expired'])->count(),
        ];
    }
}
