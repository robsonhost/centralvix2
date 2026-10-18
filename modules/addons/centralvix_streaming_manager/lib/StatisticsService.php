<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStreamingStatisticsService
{
    public static function collectForAccount($accountId)
    {
        $account = CentralVixStreamingService::getAccount($accountId);
        if (!$account || $account->status === 'cancelled') {
            return null;
        }
        $provider = CentralVixStreamingService::providerForAccount($account);
        if (!$provider) {
            return null;
        }
        $live = $provider->getLiveStats($account);
        Capsule::table('mod_centralvix_stream_stats')->insert([
            'account_id' => (int) $accountId,
            'listeners' => (int) ($live['listeners'] ?? 0),
            'peak_listeners' => (int) ($live['peak_listeners'] ?? 0),
            'bitrate' => (int) ($live['bitrate'] ?? $account->bitrate),
            'bytes_transfer' => (int) ($live['bytes'] ?? 0),
            'online' => !empty($live['online']) ? 1 : 0,
            'recorded_at' => date('Y-m-d H:i:s'),
        ]);
        self::purgeOld();
        return $live;
    }

    public static function collectAll()
    {
        $accounts = Capsule::table('mod_centralvix_stream_accounts')->whereIn('status', ['active', 'suspended'])->get();
        $count = 0;
        foreach ($accounts as $a) {
            if (self::collectForAccount($a->id)) {
                $count++;
            }
        }
        return $count;
    }

    public static function history($accountId, $hours = 24)
    {
        $since = date('Y-m-d H:i:s', strtotime('-' . (int) $hours . ' hours'));
        return Capsule::table('mod_centralvix_stream_stats')
            ->where('account_id', (int) $accountId)
            ->where('recorded_at', '>=', $since)
            ->orderBy('recorded_at', 'asc')
            ->get();
    }

    public static function summary($accountId, $days = 7)
    {
        $since = date('Y-m-d H:i:s', strtotime('-' . (int) $days . ' days'));
        $rows = Capsule::table('mod_centralvix_stream_stats')
            ->where('account_id', (int) $accountId)
            ->where('recorded_at', '>=', $since)
            ->get();
        if ($rows->isEmpty()) {
            return [
                'peak_listeners' => 0,
                'avg_listeners' => 0,
                'online_checks' => 0,
                'total_checks' => 0,
                'bytes_transfer' => 0,
            ];
        }
        $peak = 0;
        $sum = 0;
        $online = 0;
        $bytes = 0;
        foreach ($rows as $r) {
            $peak = max($peak, (int) $r->peak_listeners, (int) $r->listeners);
            $sum += (int) $r->listeners;
            if ($r->online) {
                $online++;
            }
            $bytes += (int) $r->bytes_transfer;
        }
        return [
            'peak_listeners' => $peak,
            'avg_listeners' => round($sum / count($rows), 1),
            'online_checks' => $online,
            'total_checks' => count($rows),
            'bytes_transfer' => $bytes,
        ];
    }

    public static function purgeOld($days = null)
    {
        $days = $days ?: max(7, (int) CentralVixStreamingModule::setting('stats_retention_days', 30));
        Capsule::table('mod_centralvix_stream_stats')
            ->where('recorded_at', '<', date('Y-m-d H:i:s', strtotime('-' . $days . ' days')))
            ->delete();
    }
}
