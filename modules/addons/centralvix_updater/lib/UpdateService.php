<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixUpdUpdateService
{
    public static function bootstrapApi()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    public static function rateLimit($bucket)
    {
        $max = (int) CentralVixUpdModule::dbSetting('api_rate_limit', '60');
        if ($max < 1) {
            $max = 60;
        }
        $key = 'cvix_upd_rl_' . hash('sha256', $bucket);
        $file = CentralVixUpdModule::storagePath('cache/' . $key . '.json');
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        $now = time();
        $data = ['count' => 0, 'window' => $now];
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file), true) ?: $data;
        }
        if ($now - ($data['window'] ?? 0) >= 60) {
            $data = ['count' => 0, 'window' => $now];
        }
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        if ($data['count'] > $max) {
            self::respond(['success' => false, 'message' => 'Rate limit excedido.'], 429);
        }
    }

    public static function parseInput()
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }

    public static function respond(array $payload, $code = 200, $licenseKey = null)
    {
        self::bootstrapApi();
        http_response_code($code);
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $sig = CentralVixUpdSignatureService::signApiResponse($body, $licenseKey);
        header('X-CVIX-Signature: ' . $sig);
        header('X-CVIX-Signature-Alg: HMAC-SHA256');
        echo $body;
        exit;
    }

    public static function check(array $input)
    {
        $productSlug = CentralVixUpdSecurity::sanitizeSlug($input['product'] ?? $input['product_slug'] ?? '');
        $currentVersion = CentralVixUpdSecurity::sanitizeVersion($input['current_version'] ?? $input['version'] ?? '0.0.0') ?: '0.0.0';
        $channel = CentralVixUpdSecurity::validateChannel($input['channel'] ?? 'stable');
        $licenseKey = trim((string) ($input['license_key'] ?? $input['license'] ?? ''));
        $domain = CentralVixUpdSecurity::sanitizeText($input['domain'] ?? '', 150);

        if ($productSlug === '') {
            return self::genericFail('Produto inválido.');
        }

        $product = CentralVixUpdProductService::getBySlug($productSlug);
        if (!$product) {
            CentralVixUpdLogger::logDownload(0, 0, 'failed', 'Check produto desconhecido', ['slug' => $productSlug]);
            return self::genericFail('Nenhuma atualização disponível.');
        }

        $licCheck = CentralVixUpdLicenseConnector::validateForProduct($licenseKey, $product, $domain);
        if (!$licCheck['success']) {
            CentralVixUpdLogger::logDownload(0, $product->id, 'denied', $licCheck['message']);
            return self::genericFail('Não foi possível verificar atualizações.');
        }

        $latest = CentralVixUpdVersionService::latestForProduct($product->id, $channel);
        if (!$latest || empty($latest->sha256_hash)) {
            CentralVixUpdLogger::logDownload(0, $product->id, 'check_no_update', 'Sem versões publicadas');
            return self::respond([
                'success' => true,
                'data' => [
                    'update_available' => false,
                    'current_version' => $currentVersion,
                    'latest_version' => $currentVersion,
                    'required' => false,
                ],
            ], 200, $licenseKey ?: null);
        }

        if (CentralVixUpdVersionService::compareVersions($latest->version, $latest->min_version) < 0) {
            $latest->min_version = '0.0.0';
        }

        if (CentralVixUpdVersionService::compareVersions($currentVersion, $latest->min_version) < 0) {
            CentralVixUpdLogger::logDownload($latest->id, $product->id, 'denied', 'Versão atual abaixo do mínimo');
            return self::genericFail('Atualização intermediária necessária.');
        }

        $updateAvailable = CentralVixUpdVersionService::compareVersions($latest->version, $currentVersion) > 0;
        $minPhp = $latest->min_php ?: '7.4';

        CentralVixUpdLogger::logDownload(
            $latest->id,
            $product->id,
            'check_update',
            $updateAvailable ? 'Atualização disponível' : 'Cliente atualizado',
            ['current' => $currentVersion, 'latest' => $latest->version]
        );

        $downloadInfo = null;
        if ($updateAvailable) {
            $tokenResult = CentralVixUpdDownloadService::createToken($latest->id, $licenseKey);
            if ($tokenResult['success']) {
                $downloadInfo = [
                    'url' => $tokenResult['download_url'],
                    'token' => $tokenResult['token'],
                    'expires_at' => $tokenResult['expires_at'],
                    'method' => 'POST',
                ];
            }
        }

        return self::respond([
            'success' => true,
            'data' => [
                'update_available' => $updateAvailable,
                'current_version' => $currentVersion,
                'latest_version' => $latest->version,
                'download' => $downloadInfo,
                'checksum' => $updateAvailable ? [
                    'sha256' => $latest->sha256_hash,
                    'signature' => $latest->signature,
                    'size' => (int) $latest->file_size,
                ] : null,
                'release_notes' => $latest->release_notes,
                'required' => (bool) $latest->is_required,
                'minimum_php' => $minPhp,
                'minimum_whmcs' => $latest->min_whmcs,
                'channel' => $channel,
            ],
        ], 200, $licenseKey ?: null);
    }

    protected static function genericFail($publicMessage)
    {
        return self::respond([
            'success' => false,
            'message' => $publicMessage,
            'data' => ['update_available' => false],
        ], 200);
    }
}
