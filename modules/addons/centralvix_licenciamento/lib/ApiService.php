<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixLicApiService
{
    public static function bootstrap()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    public static function authenticate()
    {
        $secret = CentralVixLicEncryptionService::ensureApiSecret();
        $header = $_SERVER['HTTP_X_CVIX_LICENSE_KEY'] ?? '';
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (strpos($auth, 'Bearer ') === 0) {
            $header = substr($auth, 7);
        }
        if ($header === '' || !hash_equals($secret, $header)) {
            self::respond(['success' => false, 'message' => 'Não autorizado.'], 401);
        }
    }

    public static function rateLimit($bucket, $maxPerMinute = 60)
    {
        $maxPerMinute = (int) CentralVixLicModule::dbSetting('api_rate_limit', $maxPerMinute);
        if ($maxPerMinute < 1) {
            $maxPerMinute = 60;
        }
        $key = 'cvix_lic_rl_' . hash('sha256', $bucket);
        $file = CentralVixLicModule::path() . '/storage/cache/' . $key . '.json';
        $dir = dirname($file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
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
        file_put_contents($file, json_encode($data));
        if ($data['count'] > $maxPerMinute) {
            self::respond(['success' => false, 'message' => 'Rate limit excedido.'], 429);
        }
    }

    public static function parseInput()
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
        return $_POST;
    }

    public static function resolveServerIp($input)
    {
        return CentralVixLicSecurity::clientIp();
    }

    public static function respond(array $payload, $code = 200, $licenseKey = null)
    {
        self::bootstrap();
        http_response_code($code);
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($licenseKey) {
            $sig = CentralVixLicEncryptionService::signResponseBody($body, $licenseKey);
            header('X-CVIX-Signature: ' . $sig);
            header('X-CVIX-Signature-Alg: HMAC-SHA256');
        }
        echo $body;
        exit;
    }

    public static function respondValidation($result)
    {
        $key = '';
        if (!empty($result['license'])) {
            $key = $result['license']->license_key;
        }
        $payload = [
            'success' => $result['success'],
            'data' => $result['data'],
        ];
        self::respond($payload, $result['code'] ?? ($result['success'] ? 200 : 400), $key);
    }

    public static function logApi($action, $licenseKey, $message, $success)
    {
        $lic = CentralVixLicLicenseService::findByKey($licenseKey);
        CentralVixLicLogger::log(
            $lic ? $lic->id : 0,
            'api_' . $action,
            $message,
            ['success' => $success, 'ip' => CentralVixLicSecurity::clientIp()],
            $lic ? (int) $lic->client_id : 0
        );
    }
}
