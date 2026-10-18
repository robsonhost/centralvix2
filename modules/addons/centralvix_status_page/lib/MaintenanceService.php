<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusMaintenanceService
{
    public static function listAll($upcoming = false, $limit = 50)
    {
        $q = Capsule::table('mod_centralvix_status_maintenance')->orderBy('start_at', 'desc');
        if ($upcoming) {
            $q->where('start_at', '>', date('Y-m-d H:i:s'))->whereIn('status', ['scheduled', 'in_progress']);
        }
        return $q->limit($limit)->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_status_maintenance')->where('id', (int) $id)->first();
    }

    public static function save(array $data, $id = 0)
    {
        $row = [
            'title' => CentralVixStatusSecurity::sanitizeText($data['title'] ?? '', 200),
            'description' => CentralVixStatusSecurity::sanitizeText($data['description'] ?? '', 2000),
            'affected_services' => json_encode(array_map('intval', (array) ($data['affected_services'] ?? []))),
            'start_at' => $data['start_at'] ?? date('Y-m-d H:i:s'),
            'end_at' => $data['end_at'] ?? date('Y-m-d H:i:s', strtotime('+1 hour')),
            'status' => preg_replace('/[^a-z_]/', '', $data['status'] ?? 'scheduled'),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            Capsule::table('mod_centralvix_status_maintenance')->where('id', (int) $id)->update($row);
            return (int) $id;
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = (int) Capsule::table('mod_centralvix_status_maintenance')->insertGetId($row);
        CentralVixStatusNotificationService::notifyMaintenanceScheduled(self::get($newId));
        return $newId;
    }

    public static function processScheduled()
    {
        $now = date('Y-m-d H:i:s');
        $starting = Capsule::table('mod_centralvix_status_maintenance')
            ->where('status', 'scheduled')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->get();
        foreach ($starting as $m) {
            Capsule::table('mod_centralvix_status_maintenance')->where('id', $m->id)->update(['status' => 'in_progress']);
            CentralVixStatusNotificationService::notifyMaintenanceStarted($m);
        }
        $ending = Capsule::table('mod_centralvix_status_maintenance')
            ->where('status', 'in_progress')
            ->where('end_at', '<=', $now)
            ->get();
        foreach ($ending as $m) {
            Capsule::table('mod_centralvix_status_maintenance')->where('id', $m->id)->update(['status' => 'completed']);
            CentralVixStatusNotificationService::notifyMaintenanceCompleted($m);
        }
    }

    public static function upcomingPublic($limit = 5)
    {
        return Capsule::table('mod_centralvix_status_maintenance')
            ->where('is_public', 1)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('end_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('start_at', 'asc')
            ->limit($limit)
            ->get();
    }
}
