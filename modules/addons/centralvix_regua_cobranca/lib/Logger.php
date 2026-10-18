<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixReguaLogger
{
    public static function log($invoiceId, $ruleId, $clientId, $channel, $status, $response = '', $messagePreview = '', $idempotencyKey = '')
    {
        if ($idempotencyKey === '') {
            $idempotencyKey = CentralVixReguaModule::idempotencyKey($invoiceId, $ruleId);
        }

        $exists = Capsule::table('mod_centralvix_regua_logs')->where('idempotency_key', $idempotencyKey)->where('channel', $channel)->first();
        if ($exists) {
            return (int) $exists->id;
        }

        return (int) Capsule::table('mod_centralvix_regua_logs')->insertGetId([
            'invoice_id' => (int) $invoiceId,
            'rule_id' => (int) $ruleId,
            'client_id' => (int) $clientId,
            'idempotency_key' => substr($idempotencyKey, 0, 100),
            'channel' => substr($channel, 0, 20),
            'status' => substr($status, 0, 30),
            'response' => CentralVixReguaSecurity::sanitizeLogResponse($response),
            'message_preview' => CentralVixReguaSecurity::sanitizeText($messagePreview, 500),
            'executed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function wasSent($invoiceId, $ruleId, $channel = null, $date = null)
    {
        $key = CentralVixReguaModule::idempotencyKey($invoiceId, $ruleId, $date);
        $q = Capsule::table('mod_centralvix_regua_logs')
            ->where('idempotency_key', $key)
            ->where('status', 'success');
        if ($channel) {
            $q->where('channel', $channel);
        }
        return $q->exists();
    }

    public static function listLogs(array $filters = [], $page = 1, $perPage = 50)
    {
        $q = Capsule::table('mod_centralvix_regua_logs as l')
            ->leftJoin('mod_centralvix_regua_rules as r', 'l.rule_id', '=', 'r.id')
            ->leftJoin('tblclients as c', 'l.client_id', '=', 'c.id')
            ->select(
                'l.*',
                'r.name as rule_name',
                Capsule::raw("CONCAT(c.firstname, ' ', c.lastname) as client_name")
            )
            ->orderBy('l.executed_at', 'desc');

        if (!empty($filters['invoice_id'])) {
            $q->where('l.invoice_id', (int) $filters['invoice_id']);
        }
        if (!empty($filters['client_id'])) {
            $q->where('l.client_id', (int) $filters['client_id']);
        }
        if (!empty($filters['rule_id'])) {
            $q->where('l.rule_id', (int) $filters['rule_id']);
        }
        if (!empty($filters['channel'])) {
            $q->where('l.channel', $filters['channel']);
        }
        if (!empty($filters['status'])) {
            $q->where('l.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $q->where('l.executed_at', '>=', $filters['date_from'] . ' 00:00:00');
        }
        if (!empty($filters['date_to'])) {
            $q->where('l.executed_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        $total = (clone $q)->count();
        $rows = $q->forPage(max(1, $page), $perPage)->get();

        return ['rows' => $rows, 'total' => $total];
    }

    public static function dashboardCounts()
    {
        $today = date('Y-m-d');
        $todayStart = $today . ' 00:00:00';
        $todayEnd = $today . ' 23:59:59';

        $dueSoon = Capsule::table('tblinvoices')
            ->whereIn('status', ['Unpaid', 'Payment Pending'])
            ->where('duedate', '>=', $today)
            ->where('duedate', '<=', date('Y-m-d', strtotime('+7 days')))
            ->count();

        $overdue = Capsule::table('tblinvoices')
            ->whereIn('status', ['Unpaid', 'Payment Pending', 'Collections'])
            ->where('duedate', '<', $today)
            ->count();

        $messagesToday = Capsule::table('mod_centralvix_regua_logs')
            ->whereBetween('executed_at', [$todayStart, $todayEnd])
            ->count();

        $successToday = Capsule::table('mod_centralvix_regua_logs')
            ->whereBetween('executed_at', [$todayStart, $todayEnd])
            ->where('status', 'success')
            ->count();

        $failedToday = Capsule::table('mod_centralvix_regua_logs')
            ->whereBetween('executed_at', [$todayStart, $todayEnd])
            ->where('status', 'failed')
            ->count();

        $openAmount = Capsule::table('tblinvoices')
            ->whereIn('status', ['Unpaid', 'Payment Pending', 'Collections'])
            ->sum('total');

        $recovered = Capsule::table('mod_centralvix_regua_logs as l')
            ->join('tblinvoices as i', 'l.invoice_id', '=', 'i.id')
            ->where('l.status', 'success')
            ->where('i.status', 'Paid')
            ->distinct()
            ->count(Capsule::raw('l.client_id'));

        return [
            'due_soon' => (int) $dueSoon,
            'overdue' => (int) $overdue,
            'messages_today' => (int) $messagesToday,
            'success_today' => (int) $successToday,
            'failed_today' => (int) $failedToday,
            'open_amount' => round((float) $openAmount, 2),
            'recovered_clients' => (int) $recovered,
        ];
    }
}
