<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusService
{
    public static function getOverallStatus()
    {
        $services = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)->where('is_public', 1)->get();
        if ($services->isEmpty()) {
            return 'operational';
        }
        $priority = [
            'major_outage' => 5,
            'partial_outage' => 4,
            'instability' => 3,
            'degraded' => 2,
            'maintenance' => 2,
            'investigating' => 3,
            'monitoring' => 2,
            'operational' => 0,
            'resolved' => 0,
        ];
        $max = 0;
        $worst = 'operational';
        foreach ($services as $s) {
            $p = isset($priority[$s->status]) ? $priority[$s->status] : 1;
            if ($p > $max) {
                $max = $p;
                $worst = $s->status;
            }
        }
        $activeIncidents = Capsule::table('mod_centralvix_status_incidents')
            ->whereIn('status', ['investigating', 'monitoring', 'partial_outage', 'major_outage', 'instability'])
            ->count();
        if ($activeIncidents > 0 && $max < 3) {
            return 'instability';
        }
        return $worst;
    }

    public static function getOverallMessage()
    {
        $status = self::getOverallStatus();
        switch ($status) {
            case 'operational':
            case 'resolved':
                return CentralVixStatusModule::lang('all_operational');
            case 'major_outage':
            case 'partial_outage':
                return CentralVixStatusModule::lang('major_issues');
            default:
                return CentralVixStatusModule::lang('some_issues');
        }
    }

    public static function dashboardStats()
    {
        $total = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)->count();
        $operational = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)->where('status', 'operational')->count();
        $problems = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)
            ->whereNotIn('status', ['operational', 'resolved', 'maintenance'])->count();
        $incidents = Capsule::table('mod_centralvix_status_incidents')
            ->whereNotIn('status', ['resolved'])->count();
        $maintenance = Capsule::table('mod_centralvix_status_maintenance')
            ->where('status', 'scheduled')->where('start_at', '>', date('Y-m-d H:i:s'))->count();
        $avgUptime = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)->avg('uptime_percentage');

        return [
            'total' => (int) $total,
            'operational' => (int) $operational,
            'problems' => (int) $problems,
            'incidents' => (int) $incidents,
            'maintenance' => (int) $maintenance,
            'avg_uptime' => round((float) $avgUptime, 2),
        ];
    }

    public static function listServices($publicOnly = false, $enabledOnly = true)
    {
        $q = Capsule::table('mod_centralvix_status_services');
        if ($publicOnly) {
            $q->where('is_public', 1);
        }
        if ($enabledOnly) {
            $q->where('enabled', 1);
        }
        return $q->orderBy('display_order')->orderBy('name')->get();
    }

    public static function getService($id)
    {
        return Capsule::table('mod_centralvix_status_services')->where('id', (int) $id)->first();
    }

    public static function saveService(array $data, $id = 0)
    {
        $row = [
            'name' => CentralVixStatusSecurity::sanitizeText($data['name'] ?? '', 150),
            'description' => CentralVixStatusSecurity::sanitizeText($data['description'] ?? '', 500),
            'category' => preg_replace('/[^a-z_]/', '', $data['category'] ?? 'custom'),
            'monitor_type' => preg_replace('/[^a-z_]/', '', $data['monitor_type'] ?? 'https'),
            'host' => CentralVixStatusSecurity::sanitizeText($data['host'] ?? '', 255),
            'port' => (int) ($data['port'] ?? 0),
            'url' => CentralVixStatusSecurity::sanitizeText($data['url'] ?? '', 500),
            'expected_http_code' => (int) ($data['expected_http_code'] ?? 200),
            'timeout' => max(3, min(60, (int) ($data['timeout'] ?? 10))),
            'status' => preg_replace('/[^a-z_]/', '', $data['status'] ?? 'operational'),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'enabled' => !empty($data['enabled']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            Capsule::table('mod_centralvix_status_services')->where('id', (int) $id)->update($row);
            return (int) $id;
        }
        $row['uptime_percentage'] = 100;
        $row['response_time'] = 0;
        $row['created_at'] = date('Y-m-d H:i:s');
        return (int) Capsule::table('mod_centralvix_status_services')->insertGetId($row);
    }

    public static function updateServiceStatus($serviceId, $status, $responseTime = null, $message = '')
    {
        $update = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($responseTime !== null) {
            $update['response_time'] = (int) $responseTime;
        }
        Capsule::table('mod_centralvix_status_services')->where('id', (int) $serviceId)->update($update);
        CentralVixStatusUptimeService::recordCheck((int) $serviceId, $status, (int) $responseTime, $message);
    }

    public static function servicesByCategory($publicOnly = true)
    {
        $grouped = [];
        foreach (self::listServices($publicOnly) as $service) {
            $cat = $service->category ?: 'custom';
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $service;
        }
        return $grouped;
    }
}
