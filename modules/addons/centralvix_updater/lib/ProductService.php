<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixUpdProductService
{
    public static function listAll($activeOnly = false)
    {
        $q = Capsule::table('mod_centralvix_updater_products')->orderBy('name');
        if ($activeOnly) {
            $q->where('active', 1);
        }
        return $q->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_updater_products')->where('id', (int) $id)->first();
    }

    public static function getBySlug($slug)
    {
        return Capsule::table('mod_centralvix_updater_products')
            ->where('slug', CentralVixUpdSecurity::sanitizeSlug($slug))
            ->where('active', 1)
            ->first();
    }

    public static function save(array $data, $id = 0)
    {
        $name = CentralVixUpdSecurity::sanitizeText($data['name'] ?? '', 150);
        $slug = CentralVixUpdSecurity::sanitizeSlug($data['slug'] ?? $name);
        if ($name === '' || $slug === '') {
            return ['success' => false, 'message' => 'Nome e slug são obrigatórios.'];
        }

        $dup = Capsule::table('mod_centralvix_updater_products')
            ->where('slug', $slug)
            ->when($id > 0, function ($q) use ($id) {
                return $q->where('id', '!=', $id);
            })
            ->first();
        if ($dup) {
            return ['success' => false, 'message' => 'Slug já em uso.'];
        }

        $row = [
            'name' => $name,
            'slug' => $slug,
            'description' => CentralVixUpdSecurity::sanitizeText($data['description'] ?? '', 2000),
            'require_license' => !empty($data['require_license']) ? 1 : 0,
            'license_product_slug' => CentralVixUpdSecurity::sanitizeSlug($data['license_product_slug'] ?? ''),
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            Capsule::table('mod_centralvix_updater_products')->where('id', $id)->update($row);
            CentralVixUpdLogger::log('product_update', 'Produto atualizado: ' . $name, [], $id);
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_updater_products')->insertGetId($row);
            CentralVixUpdLogger::log('product_create', 'Produto criado: ' . $name, [], $id);
        }

        return ['success' => true, 'id' => $id];
    }

    public static function countVersions($productId)
    {
        return (int) Capsule::table('mod_centralvix_updater_versions')
            ->where('product_id', (int) $productId)->count();
    }
}
