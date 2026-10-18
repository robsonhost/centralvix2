<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixLicProductService
{
    public static function listAll()
    {
        return Capsule::table('mod_centralvix_license_products')->orderBy('name')->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_license_products')->where('id', (int) $id)->first();
    }

    public static function getByWhmcsProduct($whmcsProductId)
    {
        return Capsule::table('mod_centralvix_license_products')
            ->where('whmcs_product_id', (int) $whmcsProductId)
            ->where('active', 1)
            ->first();
    }

    public static function save(array $data, $id = 0)
    {
        $row = [
            'name' => CentralVixLicSecurity::sanitizeText($data['name'] ?? '', 150),
            'slug' => CentralVixLicSecurity::sanitizeText($data['slug'] ?? '', 80),
            'whmcs_product_id' => (int) ($data['whmcs_product_id'] ?? 0),
            'max_activations' => max(1, (int) ($data['max_activations'] ?? 1)),
            'grace_period_hours' => max(0, (int) ($data['grace_period_hours'] ?? 24)),
            'validity_days' => max(0, (int) ($data['validity_days'] ?? 365)),
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($row['name'] === '') {
            return ['success' => false, 'message' => 'Nome obrigatório.'];
        }
        if ($id > 0) {
            Capsule::table('mod_centralvix_license_products')->where('id', $id)->update($row);
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_license_products')->insertGetId($row);
        }
        return ['success' => true, 'id' => $id];
    }
}
