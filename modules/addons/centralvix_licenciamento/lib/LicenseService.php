<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixLicLicenseService
{
    const STATUSES = [
        'pendente' => 'Pendente',
        'ativa' => 'Ativa',
        'suspensa' => 'Suspensa',
        'expirada' => 'Expirada',
        'cancelada' => 'Cancelada',
    ];

    public static function statusLabel($s)
    {
        return self::STATUSES[$s] ?? $s;
    }

    public static function dashboardStats()
    {
        return [
            'total' => Capsule::table('mod_centralvix_licenses')->count(),
            'active' => Capsule::table('mod_centralvix_licenses')->where('status', 'ativa')->count(),
            'suspended' => Capsule::table('mod_centralvix_licenses')->where('status', 'suspensa')->count(),
            'expired' => Capsule::table('mod_centralvix_licenses')->where('status', 'expirada')->count(),
            'pending' => Capsule::table('mod_centralvix_licenses')->where('status', 'pendente')->count(),
        ];
    }

    public static function findByKey($licenseKey)
    {
        $hash = CentralVixLicEncryptionService::hashLicenseKeyForLookup($licenseKey);
        return Capsule::table('mod_centralvix_licenses')->where('license_key_hash', $hash)->first();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_licenses')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function listAll($limit = 300)
    {
        return Capsule::table('mod_centralvix_licenses as l')
            ->leftJoin('mod_centralvix_license_products as p', 'p.id', '=', 'l.product_id')
            ->leftJoin('tblclients as c', 'c.id', '=', 'l.client_id')
            ->select('l.*', 'p.name as product_name', Capsule::raw("CONCAT(c.firstname,' ',c.lastname) as client_name"))
            ->orderBy('l.id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function listByClient($clientId)
    {
        return Capsule::table('mod_centralvix_licenses as l')
            ->leftJoin('mod_centralvix_license_products as p', 'p.id', '=', 'l.product_id')
            ->select('l.*', 'p.name as product_name')
            ->where('l.client_id', (int) $clientId)
            ->where('status', '!=', 'cancelada')
            ->orderBy('l.id', 'desc')
            ->get();
    }

    public static function create(array $data)
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $clientId = (int) ($data['client_id'] ?? 0);
        $product = CentralVixLicProductService::get($productId);
        if (!$product || !$clientId) {
            return ['success' => false, 'message' => 'Produto e cliente obrigatórios.'];
        }

        $key = CentralVixLicLicenseGenerator::generateUnique();
        $validityDays = (int) ($product->validity_days ?: 365);
        $expires = $validityDays > 0 ? date('Y-m-d H:i:s', strtotime('+' . $validityDays . ' days')) : null;

        $id = Capsule::table('mod_centralvix_licenses')->insertGetId([
            'license_key' => $key,
            'license_key_hash' => CentralVixLicEncryptionService::hashLicenseKeyForLookup($key),
            'client_id' => $clientId,
            'service_id' => (int) ($data['service_id'] ?? 0),
            'product_id' => $productId,
            'domain' => CentralVixLicSecurity::sanitizeText($data['domain'] ?? '', 150),
            'ip_address' => '',
            'status' => $data['status'] ?? 'pendente',
            'issued_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expires,
            'last_check' => null,
            'max_activations' => (int) ($data['max_activations'] ?? $product->max_activations),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixLicLogger::log($id, 'created', 'Licença criada', ['product_id' => $productId], $clientId);
        CentralVixLicNotificationService::licenseCreated($id, $clientId, $key, $product->name);

        return ['success' => true, 'id' => $id, 'license_key' => $key];
    }

    public static function createFromService($serviceId)
    {
        $hosting = Capsule::table('tblhosting')->where('id', (int) $serviceId)->first();
        if (!$hosting) {
            return null;
        }
        $product = CentralVixLicProductService::getByWhmcsProduct($hosting->packageid);
        if (!$product) {
            return null;
        }
        $exists = Capsule::table('mod_centralvix_licenses')->where('service_id', (int) $serviceId)->first();
        if ($exists) {
            return $exists;
        }
        $r = self::create([
            'client_id' => (int) $hosting->userid,
            'service_id' => (int) $serviceId,
            'product_id' => (int) $product->id,
            'domain' => $hosting->domain ?? '',
            'status' => 'ativa',
        ]);
        if ($r['success']) {
            self::setStatus($r['id'], 'ativa', 'Ativada via provisionamento WHMCS');
        }
        return $r;
    }

    public static function setStatus($id, $status, $note = '')
    {
        if (!isset(self::STATUSES[$status])) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }
        $lic = self::get($id);
        if (!$lic) {
            return ['success' => false, 'message' => 'Licença não encontrada.'];
        }
        Capsule::table('mod_centralvix_licenses')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixLicLogger::log($id, 'status_' . $status, $note ?: ('Status: ' . self::statusLabel($status)), [], (int) $lic->client_id);
        return ['success' => true];
    }

    public static function renew($id, $days = 365)
    {
        $lic = self::get($id);
        if (!$lic) {
            return ['success' => false, 'message' => 'Licença não encontrada.'];
        }
        $base = ($lic->expires_at && $lic->expires_at > date('Y-m-d H:i:s')) ? $lic->expires_at : date('Y-m-d H:i:s');
        $newExpiry = date('Y-m-d H:i:s', strtotime($base . ' +' . (int) $days . ' days'));
        Capsule::table('mod_centralvix_licenses')->where('id', $id)->update([
            'expires_at' => $newExpiry,
            'status' => $lic->status === 'expirada' ? 'ativa' : $lic->status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixLicLogger::log($id, 'renewed', 'Renovada por ' . $days . ' dias', ['expires_at' => $newExpiry], (int) $lic->client_id);
        return ['success' => true, 'expires_at' => $newExpiry];
    }

    public static function changeDomain($id, $domain)
    {
        $lic = self::get($id);
        if (!$lic) {
            return ['success' => false, 'message' => 'Licença não encontrada.'];
        }
        if (!CentralVixLicSecurity::validateDomain($domain)) {
            return ['success' => false, 'message' => 'Domínio inválido.'];
        }
        Capsule::table('mod_centralvix_licenses')->where('id', $id)->update([
            'domain' => strtolower(trim($domain)),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixLicLogger::log($id, 'domain_changed', 'Domínio alterado para ' . $domain, [], (int) $lic->client_id);
        return ['success' => true];
    }

    public static function resetActivations($id)
    {
        $lic = self::get($id);
        if (!$lic) {
            return ['success' => false, 'message' => 'Licença não encontrada.'];
        }
        Capsule::table('mod_centralvix_license_activations')->where('license_id', $id)->delete();
        CentralVixLicLogger::log($id, 'activations_reset', 'Ativações resetadas', [], (int) $lic->client_id);
        return ['success' => true];
    }

    public static function activations($licenseId)
    {
        return Capsule::table('mod_centralvix_license_activations')
            ->where('license_id', (int) $licenseId)
            ->where('status', 'active')
            ->get();
    }

    public static function activationCount($licenseId)
    {
        return Capsule::table('mod_centralvix_license_activations')
            ->where('license_id', (int) $licenseId)
            ->where('status', 'active')
            ->count();
    }

    public static function touchLastCheck($licenseId)
    {
        Capsule::table('mod_centralvix_licenses')->where('id', (int) $licenseId)->update([
            'last_check' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function checkExpired()
    {
        $now = date('Y-m-d H:i:s');
        $ids = Capsule::table('mod_centralvix_licenses')
            ->whereIn('status', ['ativa', 'pendente'])
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->pluck('id');
        foreach ($ids as $id) {
            self::setStatus($id, 'expirada', 'Expirada automaticamente');
        }
    }

    public static function toPublicPayload($lic, $product = null)
    {
        if (!$product && $lic->product_id) {
            $product = CentralVixLicProductService::get($lic->product_id);
        }
        $grace = $product ? (int) $product->grace_period_hours : (int) CentralVixLicModule::dbSetting('default_grace_hours', '24');
        return [
            'valid' => in_array($lic->status, ['ativa', 'pendente'], true) && (!$lic->expires_at || $lic->expires_at >= date('Y-m-d H:i:s')),
            'status' => $lic->status,
            'status_label' => self::statusLabel($lic->status),
            'product' => $product ? $product->name : '',
            'domain' => $lic->domain,
            'expires_at' => $lic->expires_at,
            'issued_at' => $lic->issued_at,
            'max_activations' => (int) $lic->max_activations,
            'activations_used' => self::activationCount($lic->id),
            'grace_period_hours' => $grace,
            'last_check' => $lic->last_check,
        ];
    }
}
