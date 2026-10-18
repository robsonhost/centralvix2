<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixUpdLicenseConnector
{
    public static function licenciamentoAvailable()
    {
        return Capsule::schema()->hasTable('mod_centralvix_licenses')
            && is_file(dirname(__DIR__, 2) . '/centralvix_licenciamento/lib/ValidationService.php');
    }

    public static function loadLicenciamento()
    {
        if (!self::licenciamentoAvailable()) {
            return false;
        }
        $base = dirname(__DIR__, 2) . '/centralvix_licenciamento';
        require_once $base . '/lib/Security.php';
        CentralVixLicModule::autoload();
        return true;
    }

    public static function validateForProduct($licenseKey, $product, $domain = '')
    {
        if (!$product->require_license) {
            return ['success' => true, 'message' => 'Licença não exigida.'];
        }

        if ($licenseKey === '') {
            return ['success' => false, 'message' => 'Licença obrigatória para este produto.'];
        }

        if (!self::loadLicenciamento()) {
            $hash = CentralVixUpdLicenseConnector::hashLicenseKey($licenseKey);
            $lic = Capsule::table('mod_centralvix_licenses')->where('license_key_hash', $hash)->first();
            if (!$lic) {
                return ['success' => false, 'message' => 'Licença inválida.'];
            }
            if (!in_array($lic->status, ['ativa', 'pendente'], true)) {
                return ['success' => false, 'message' => 'Licença inativa.'];
            }
            if ($lic->expires_at && $lic->expires_at < date('Y-m-d H:i:s')) {
                return ['success' => false, 'message' => 'Licença expirada.'];
            }
            if ($product->license_product_slug) {
                $licProduct = Capsule::table('mod_centralvix_license_products')
                    ->where('id', $lic->product_id)->first();
                if ($licProduct && $licProduct->slug !== $product->license_product_slug) {
                    return ['success' => false, 'message' => 'Licença não corresponde ao produto.'];
                }
            }
            return ['success' => true, 'license_id' => $lic->id];
        }

        $domain = $domain !== '' ? $domain : ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $result = CentralVixLicValidationService::validate($licenseKey, $domain, CentralVixUpdSecurity::clientIp());
        if (!$result['success']) {
            return ['success' => false, 'message' => $result['data']['message'] ?? 'Licença inválida.'];
        }

        if ($product->license_product_slug) {
            $slug = $result['data']['product_slug'] ?? '';
            if ($slug && $slug !== $product->license_product_slug) {
                return ['success' => false, 'message' => 'Licença não autorizada para este produto.'];
            }
        }

        return ['success' => true, 'license_id' => $result['license']->id ?? 0];
    }

    public static function hashLicenseKey($licenseKey)
    {
        if ($licenseKey === '') {
            return null;
        }
        if (self::licenciamentoAvailable()) {
            self::loadLicenciamento();
            if (class_exists('CentralVixLicEncryptionService')) {
                return CentralVixLicEncryptionService::hashLicenseKeyForLookup($licenseKey);
            }
        }
        return hash('sha256', CentralVixUpdModule::dbSetting('license_pepper', 'cvix') . '|' . $licenseKey);
    }
}
