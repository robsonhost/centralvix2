<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixReguaBillingRuleService
{
    public static function listAll($activeOnly = false)
    {
        $q = Capsule::table('mod_centralvix_regua_rules')->orderBy('display_order')->orderBy('id');
        if ($activeOnly) {
            $q->where('active', 1);
        }
        return $q->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_regua_rules')->where('id', (int) $id)->first();
    }

    public static function save(array $data, $id = 0)
    {
        $row = [
            'name' => CentralVixReguaSecurity::sanitizeText($data['name'] ?? '', 150),
            'active' => !empty($data['active']) ? 1 : 0,
            'days_relative' => max(0, (int) ($data['days_relative'] ?? 0)),
            'timing' => in_array($data['timing'] ?? '', ['before', 'on', 'after'], true) ? $data['timing'] : 'before',
            'invoice_status' => CentralVixReguaSecurity::sanitizeText($data['invoice_status'] ?? 'Unpaid', 100),
            'send_email' => !empty($data['send_email']) ? 1 : 0,
            'send_whatsapp' => !empty($data['send_whatsapp']) ? 1 : 0,
            'template_id' => (int) ($data['template_id'] ?? 0),
            'time_from' => self::normalizeTime($data['time_from'] ?? '08:00'),
            'time_to' => self::normalizeTime($data['time_to'] ?? '20:00'),
            'product_ids' => json_encode(CentralVixReguaSecurity::sanitizeJsonIds($data['product_ids'] ?? [])),
            'product_group_ids' => json_encode(CentralVixReguaSecurity::sanitizeJsonIds($data['product_group_ids'] ?? [])),
            'client_ids' => json_encode(CentralVixReguaSecurity::sanitizeJsonIds($data['client_ids'] ?? [])),
            'client_group_ids' => json_encode(CentralVixReguaSecurity::sanitizeJsonIds($data['client_group_ids'] ?? [])),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id) {
            Capsule::table('mod_centralvix_regua_rules')->where('id', (int) $id)->update($row);
            return (int) $id;
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        return (int) Capsule::table('mod_centralvix_regua_rules')->insertGetId($row);
    }

    public static function delete($id)
    {
        Capsule::table('mod_centralvix_regua_rules')->where('id', (int) $id)->delete();
        return true;
    }

    public static function toggle($id)
    {
        $rule = self::get($id);
        if (!$rule) {
            return false;
        }
        Capsule::table('mod_centralvix_regua_rules')->where('id', (int) $id)->update([
            'active' => $rule->active ? 0 : 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    public static function timingLabel($rule)
    {
        $days = (int) $rule->days_relative;
        if ($rule->timing === 'before') {
            return $days . ' dia(s) antes';
        }
        if ($rule->timing === 'on') {
            return 'No vencimento';
        }
        return $days . ' dia(s) após';
    }

    private static function normalizeTime($time)
    {
        $time = trim((string) $time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }
        return '08:00:00';
    }
}
