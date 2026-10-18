<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixLicValidationService
{
    public static function validate($licenseKey, $domain, $serverIp = null)
    {
        $lic = CentralVixLicLicenseService::findByKey($licenseKey);
        if (!$lic) {
            return self::fail('invalid', 'Licença inválida.', 404);
        }

        CentralVixLicLicenseService::touchLastCheck($lic->id);
        $product = CentralVixLicProductService::get($lic->product_id);

        if ($lic->status === 'cancelada' || $lic->status === 'suspensa') {
            return self::fail('inactive', 'Licença ' . CentralVixLicLicenseService::statusLabel($lic->status), 403, $lic, $product);
        }

        if ($lic->expires_at && $lic->expires_at < date('Y-m-d H:i:s')) {
            CentralVixLicLicenseService::setStatus($lic->id, 'expirada', 'Validação após expiração');
            return self::fail('expired', 'Licença expirada.', 403, $lic, $product);
        }

        if ($lic->domain && $domain && strtolower($lic->domain) !== strtolower($domain)) {
            return self::fail('domain_mismatch', 'Domínio não autorizado para esta licença.', 403, $lic, $product);
        }

        $payload = CentralVixLicLicenseService::toPublicPayload($lic, $product);
        $payload['message'] = 'Licença válida.';
        $payload['checked_at'] = date('c');

        CentralVixLicLogger::log($lic->id, 'api_validate', 'Validação OK', [
            'domain' => $domain,
            'server_ip' => $serverIp,
        ], (int) $lic->client_id);

        return ['success' => true, 'code' => 200, 'data' => $payload, 'license' => $lic];
    }

    public static function activate($licenseKey, $domain, $serverIp)
    {
        if (!CentralVixLicSecurity::validateDomain($domain)) {
            return self::fail('invalid_domain', 'Domínio inválido.');
        }
        if (!$serverIp || !CentralVixLicSecurity::validateIp($serverIp)) {
            return self::fail('invalid_ip', 'IP do servidor inválido ou não detectado.');
        }

        $validation = self::validate($licenseKey, $domain, $serverIp);
        if (!$validation['success']) {
            return $validation;
        }

        $lic = $validation['license'];
        $existing = Capsule::table('mod_centralvix_license_activations')
            ->where('license_id', $lic->id)
            ->where('domain', strtolower($domain))
            ->where('status', 'active')
            ->first();

        if ($existing) {
            Capsule::table('mod_centralvix_license_activations')->where('id', $existing->id)->update([
                'ip_address' => $serverIp,
                'last_seen' => date('Y-m-d H:i:s'),
            ]);
            CentralVixLicLicenseService::setStatus($lic->id, 'ativa', 'Revalidação de ativação');
            return ['success' => true, 'code' => 200, 'data' => array_merge($validation['data'], ['activation' => 'updated']), 'license' => $lic];
        }

        $count = CentralVixLicLicenseService::activationCount($lic->id);
        if ($count >= (int) $lic->max_activations) {
            return self::fail('max_activations', 'Limite de ativações atingido.', 403, $lic);
        }

        Capsule::table('mod_centralvix_license_activations')->insert([
            'license_id' => (int) $lic->id,
            'domain' => strtolower($domain),
            'ip_address' => $serverIp,
            'status' => 'active',
            'activated_at' => date('Y-m-d H:i:s'),
            'last_seen' => date('Y-m-d H:i:s'),
        ]);

        Capsule::table('mod_centralvix_licenses')->where('id', $lic->id)->update([
            'domain' => strtolower($domain),
            'ip_address' => $serverIp,
            'status' => 'ativa',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixLicLogger::log($lic->id, 'activated', 'Ativação: ' . $domain, ['ip' => $serverIp], (int) $lic->client_id);

        $validation['data']['activation'] = 'created';
        $validation['data']['activations_used'] = CentralVixLicLicenseService::activationCount($lic->id);
        return ['success' => true, 'code' => 200, 'data' => $validation['data'], 'license' => $lic];
    }

    public static function deactivate($licenseKey, $domain, $serverIp)
    {
        $lic = CentralVixLicLicenseService::findByKey($licenseKey);
        if (!$lic) {
            return self::fail('invalid', 'Licença inválida.', 404);
        }

        Capsule::table('mod_centralvix_license_activations')
            ->where('license_id', $lic->id)
            ->where('domain', strtolower($domain))
            ->update(['status' => 'revoked', 'revoked_at' => date('Y-m-d H:i:s')]);

        CentralVixLicLogger::log($lic->id, 'deactivated', 'Desativação: ' . $domain, ['ip' => $serverIp], (int) $lic->client_id);

        return [
            'success' => true,
            'code' => 200,
            'data' => ['message' => 'Ativação revogada.', 'domain' => $domain],
            'license' => $lic,
        ];
    }

    protected static function fail($code, $message, $http = 400, $lic = null, $product = null)
    {
        $data = ['valid' => false, 'error_code' => $code, 'message' => $message];
        if ($lic && $product) {
            $data['grace_period_hours'] = (int) ($product->grace_period_hours ?? 24);
        }
        return ['success' => false, 'code' => $http, 'data' => $data, 'license' => $lic];
    }
}
