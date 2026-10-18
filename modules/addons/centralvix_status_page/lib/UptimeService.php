<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusUptimeService
{
    public static function recordCheck($serviceId, $status, $responseTime, $message = '')
    {
        Capsule::table('mod_centralvix_status_checks')->insert([
            'service_id' => (int) $serviceId,
            'status' => substr($status, 0, 50),
            'response_time' => (int) $responseTime,
            'message' => CentralVixStatusSecurity::sanitizeText($message, 500),
            'checked_at' => date('Y-m-d H:i:s'),
        ]);
        self::recalculateUptime($serviceId);
        self::purgeOldChecks(90);
    }

    public static function recalculateUptime($serviceId, $days = 30)
    {
        $since = date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days'));
        $checks = Capsule::table('mod_centralvix_status_checks')
            ->where('service_id', (int) $serviceId)
            ->where('checked_at', '>=', $since)
            ->get();
        if ($checks->isEmpty()) {
            return 100.0;
        }
        $ok = 0;
        foreach ($checks as $c) {
            if (in_array($c->status, ['operational', 'resolved'], true)) {
                $ok++;
            }
        }
        $pct = round(($ok / count($checks)) * 100, 2);
        Capsule::table('mod_centralvix_status_services')->where('id', (int) $serviceId)->update([
            'uptime_percentage' => $pct,
        ]);
        return $pct;
    }

    public static function getChartData($serviceId, $period = '24h')
    {
        $hours = 24;
        if ($period === '7d') {
            $hours = 24 * 7;
        } elseif ($period === '30d') {
            $hours = 24 * 30;
        }
        $since = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hours'));
        return Capsule::table('mod_centralvix_status_checks')
            ->where('service_id', (int) $serviceId)
            ->where('checked_at', '>=', $since)
            ->orderBy('checked_at', 'asc')
            ->get(['checked_at', 'status', 'response_time']);
    }

    public static function getAggregateChart($period = '24h')
    {
        $hours = $period === '7d' ? 168 : ($period === '30d' ? 720 : 24);
        $since = date('Y-m-d H:i:s', strtotime('-' . $hours . ' hours'));
        $rows = Capsule::table('mod_centralvix_status_checks')
            ->where('checked_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(checked_at, '%Y-%m-%d %H:00:00') as bucket, AVG(response_time) as avg_rt, SUM(CASE WHEN status IN ('operational','resolved') THEN 1 ELSE 0 END) as ok_count, COUNT(*) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();
        return $rows;
    }

    public static function purgeOldChecks($days = 90)
    {
        Capsule::table('mod_centralvix_status_checks')
            ->where('checked_at', '<', date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days')))
            ->delete();
    }
}
