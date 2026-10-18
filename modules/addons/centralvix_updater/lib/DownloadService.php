<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixUpdDownloadService
{
    public static function tokenTtlMinutes()
    {
        $m = (int) CentralVixUpdModule::dbSetting('download_token_ttl', '15');
        return $m > 0 ? $m : 15;
    }

    public static function createToken($versionId, $licenseKey = '')
    {
        $version = CentralVixUpdVersionService::get($versionId);
        if (!$version || !$version->active) {
            return ['success' => false, 'message' => 'Versão indisponível.'];
        }

        $path = CentralVixUpdVersionService::resolveFilePath($version);
        if (!$path) {
            return ['success' => false, 'message' => 'Pacote não encontrado.'];
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + self::tokenTtlMinutes() * 60);

        $cacheFile = CentralVixUpdModule::storagePath('cache/tokens/' . $tokenHash . '.json');
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0750, true);
        }

        file_put_contents($cacheFile, json_encode([
            'version_id' => (int) $versionId,
            'product_id' => (int) $version->product_id,
            'license_key_hash' => CentralVixUpdLicenseConnector::hashLicenseKey($licenseKey),
            'expires_at' => $expires,
            'ip' => CentralVixUpdSecurity::clientIp(),
            'used' => false,
        ]), LOCK_EX);

        CentralVixUpdLogger::logDownload(
            $versionId,
            $version->product_id,
            'token_issued',
            'Token de download emitido',
            ['token_hash' => $tokenHash, 'license_key_hash' => CentralVixUpdLicenseConnector::hashLicenseKey($licenseKey)]
        );

        return [
            'success' => true,
            'token' => $token,
            'expires_at' => $expires,
            'download_url' => CentralVixUpdModule::apiBaseUrl() . '/download.php',
        ];
    }

    public static function consumeToken($token)
    {
        $token = preg_replace('/[^a-f0-9]/', '', strtolower((string) $token));
        if (strlen($token) !== 64) {
            return ['success' => false, 'message' => 'Token inválido.', 'status' => 'denied'];
        }

        $tokenHash = hash('sha256', $token);
        $cacheFile = CentralVixUpdModule::storagePath('cache/tokens/' . $tokenHash . '.json');
        if (!is_file($cacheFile)) {
            return ['success' => false, 'message' => 'Token expirado ou inválido.', 'status' => 'expired'];
        }

        $data = json_decode(file_get_contents($cacheFile), true);
        if (!is_array($data)) {
            @unlink($cacheFile);
            return ['success' => false, 'message' => 'Token corrompido.', 'status' => 'failed'];
        }

        if (!empty($data['used'])) {
            return ['success' => false, 'message' => 'Token já utilizado.', 'status' => 'denied'];
        }

        if ($data['expires_at'] < date('Y-m-d H:i:s')) {
            @unlink($cacheFile);
            CentralVixUpdLogger::logDownload(
                $data['version_id'] ?? 0,
                $data['product_id'] ?? 0,
                'expired',
                'Token expirado'
            );
            return ['success' => false, 'message' => 'Token expirado.', 'status' => 'expired'];
        }

        $clientIp = CentralVixUpdSecurity::clientIp();
        if (!empty($data['ip']) && $data['ip'] !== $clientIp) {
            CentralVixUpdLogger::logDownload(
                $data['version_id'] ?? 0,
                $data['product_id'] ?? 0,
                'denied',
                'IP divergente no download',
                ['expected' => $data['ip'], 'got' => $clientIp]
            );
            return ['success' => false, 'message' => 'Token não válido para este IP.', 'status' => 'denied'];
        }

        $version = CentralVixUpdVersionService::get($data['version_id']);
        $path = CentralVixUpdVersionService::resolveFilePath($version);
        if (!$path) {
            return ['success' => false, 'message' => 'Pacote indisponível.', 'status' => 'failed'];
        }

        $data['used'] = true;
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);
        @unlink($cacheFile);

        CentralVixUpdLogger::logDownload(
            $version->id,
            $version->product_id,
            'downloaded',
            'Download concluído',
            ['token_hash' => $tokenHash]
        );

        return [
            'success' => true,
            'path' => $path,
            'version' => $version,
            'filename' => basename($path),
        ];
    }

    public static function purgeExpiredTokens()
    {
        $dir = CentralVixUpdModule::storagePath('cache/tokens');
        if (!is_dir($dir)) {
            return 0;
        }
        $count = 0;
        foreach (glob($dir . '/*.json') as $file) {
            $data = json_decode(file_get_contents($file), true);
            if (!$data || ($data['expires_at'] ?? '') < date('Y-m-d H:i:s')) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }
}
