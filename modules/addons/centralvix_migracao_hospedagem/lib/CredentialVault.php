<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigCredentialVault
{
    public static function ensureMasterKey()
    {
        $key = CentralVixMigModule::dbSetting('vault_master_key');
        if ($key === '') {
            $key = base64_encode(random_bytes(32));
            CentralVixMigModule::setDbSetting('vault_master_key', $key);
        }
        return base64_decode($key, true);
    }

    public static function encrypt(array $payload)
    {
        $key = self::ensureMasterKey();
        if (strlen($key) !== 32) {
            throw new RuntimeException('Chave de vault inválida.');
        }
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Falha ao criptografar credenciais.');
        }
        return base64_encode($iv . $tag . $cipher);
    }

    public static function decrypt($blob)
    {
        if ($blob === '' || $blob === null) {
            return null;
        }
        $raw = base64_decode($blob, true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }
        $key = self::ensureMasterKey();
        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $cipher = substr($raw, 28);
        $json = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function store($requestId, array $credentials)
    {
        $clean = [
            'host' => CentralVixMigSecurity::sanitizeText($credentials['host'] ?? '', 150),
            'port' => (int) ($credentials['port'] ?? 0),
            'username' => CentralVixMigSecurity::sanitizeText($credentials['username'] ?? '', 120),
            'password' => (string) ($credentials['password'] ?? ''),
        ];
        if ($clean['password'] === '') {
            return ['success' => false, 'message' => 'Senha é obrigatória quando credenciais são informadas.'];
        }
        $encrypted = self::encrypt($clean);
        Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $requestId)->update([
            'credentials_blob' => $encrypted,
            'credentials_provided' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixMigLogger::log($requestId, 'credentials_stored', 'Credenciais armazenadas (criptografadas)', [], 'admin', CentralVixMigSecurity::adminId());
        return ['success' => true];
    }

    public static function hasCredentials($request)
    {
        return !empty($request->credentials_provided) && !empty($request->credentials_blob) && empty($request->credentials_purged_at);
    }

    public static function maskedView($request)
    {
        if (!self::hasCredentials($request)) {
            return null;
        }
        $data = self::decrypt($request->credentials_blob);
        if (!$data) {
            return ['host' => '—', 'port' => '—', 'username' => '—', 'password' => '—'];
        }
        return [
            'host' => $data['host'] ?? '',
            'port' => (string) ($data['port'] ?? ''),
            'username' => $data['username'] ?? '',
            'password' => '********',
        ];
    }

    public static function revealForAdmin($requestId, $adminId)
    {
        $request = CentralVixMigMigrationService::get($requestId);
        if (!$request || !CentralVixMigSecurity::isAdminSession()) {
            return null;
        }
        if (!self::hasCredentials($request)) {
            return null;
        }
        CentralVixMigLogger::log($requestId, 'credentials_reveal', 'Admin visualizou credenciais', ['admin_id' => $adminId], 'admin', $adminId);
        return self::decrypt($request->credentials_blob);
    }

    public static function purge($requestId, $reason = 'conclusao')
    {
        Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $requestId)->update([
            'credentials_blob' => null,
            'credentials_provided' => 0,
            'credentials_purged_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixMigLogger::log($requestId, 'credentials_purged', 'Credenciais excluídas: ' . $reason, [], 'system', 0);
    }

    public static function purgeIfCompleted($request)
    {
        if (!$request) {
            return;
        }
        $auto = CentralVixMigModule::dbSetting('auto_purge_credentials', '1');
        if ($auto !== '1') {
            return;
        }
        if (in_array($request->status, ['concluida', 'cancelada'], true) && self::hasCredentials($request)) {
            self::purge($request->id, $request->status);
        }
    }
}
