<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJScheduleService
{
    public static function listSchedules($accountId, $clientId)
    {
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        return Capsule::table('mod_centralvix_autodj_schedules as s')
            ->leftJoin('mod_centralvix_autodj_playlists as p', 's.playlist_id', '=', 'p.id')
            ->where('s.account_id', (int) $accountId)
            ->where('s.client_id', (int) $clientId)
            ->select('s.*', 'p.name as playlist_name')
            ->orderBy('s.priority', 'desc')
            ->orderBy('s.time_start')
            ->get();
    }

    public static function save(array $data, $clientId, $id = 0)
    {
        $accountId = (int) ($data['account_id'] ?? 0);
        CentralVixAutoDJService::assertAccountOwner($accountId, $clientId, !empty($_SESSION['adminid']));
        $days = isset($data['days_of_week']) ? array_map('intval', (array) $data['days_of_week']) : [];
        $row = [
            'account_id' => $accountId,
            'client_id' => (int) $clientId,
            'title' => CentralVixAutoDJSecurity::sanitizeText($data['title'] ?? '', 150),
            'schedule_date' => !empty($data['schedule_date']) ? $data['schedule_date'] : null,
            'days_of_week' => json_encode(array_values(array_unique($days))),
            'time_start' => self::normalizeTime($data['time_start'] ?? '08:00'),
            'time_end' => self::normalizeTime($data['time_end'] ?? '09:00'),
            'playlist_id' => (int) ($data['playlist_id'] ?? 0),
            'priority' => (int) ($data['priority'] ?? 0),
            'schedule_type' => preg_replace('/[^a-z_]/', '', $data['schedule_type'] ?? 'program'),
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $conflicts = self::detectConflicts($accountId, $clientId, $row, $id);
        if ($id) {
            Capsule::table('mod_centralvix_autodj_schedules')->where('id', (int) $id)
                ->where('client_id', (int) $clientId)->update($row);
            $scheduleId = (int) $id;
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $scheduleId = (int) Capsule::table('mod_centralvix_autodj_schedules')->insertGetId($row);
        }
        $schedule = Capsule::table('mod_centralvix_autodj_schedules')->where('id', $scheduleId)->first();
        $account = CentralVixAutoDJService::getAccount($accountId);
        $provider = CentralVixAutoDJStreamingConnector::provider($account);
        if ($provider) {
            $provider->applySchedule($account, $schedule);
        }
        return [
            'success' => true,
            'id' => $scheduleId,
            'conflicts' => $conflicts,
            'message' => count($conflicts) ? 'Salvo com alertas de conflito.' : 'Agendamento salvo.',
        ];
    }

    public static function detectConflicts($accountId, $clientId, array $candidate, $excludeId = 0)
    {
        $q = Capsule::table('mod_centralvix_autodj_schedules')
            ->where('account_id', (int) $accountId)
            ->where('client_id', (int) $clientId)
            ->where('active', 1);
        if ($excludeId) {
            $q->where('id', '!=', (int) $excludeId);
        }
        $existing = $q->get();
        $conflicts = [];
        $cDays = json_decode($candidate['days_of_week'] ?? '[]', true) ?: [];
        foreach ($existing as $ex) {
            if ($candidate['schedule_date'] && $ex->schedule_date && $candidate['schedule_date'] !== $ex->schedule_date) {
                continue;
            }
            $eDays = json_decode($ex->days_of_week ?: '[]', true) ?: [];
            if (!self::daysOverlap($cDays, $eDays, $candidate['schedule_date'], $ex->schedule_date)) {
                continue;
            }
            if (self::timeOverlap($candidate['time_start'], $candidate['time_end'], $ex->time_start, $ex->time_end)) {
                $conflicts[] = [
                    'id' => $ex->id,
                    'title' => $ex->title,
                    'time_start' => substr($ex->time_start, 0, 5),
                    'time_end' => substr($ex->time_end, 0, 5),
                ];
            }
        }
        return $conflicts;
    }

    private static function daysOverlap($a, $b, $dateA, $dateB)
    {
        if ($dateA && $dateB) {
            return $dateA === $dateB;
        }
        if (empty($a) || empty($b)) {
            return true;
        }
        return count(array_intersect($a, $b)) > 0;
    }

    private static function timeOverlap($s1, $e1, $s2, $e2)
    {
        $s1 = strtotime('1970-01-01 ' . substr($s1, 0, 8));
        $e1 = strtotime('1970-01-01 ' . substr($e1, 0, 8));
        $s2 = strtotime('1970-01-01 ' . substr($s2, 0, 8));
        $e2 = strtotime('1970-01-01 ' . substr($e2, 0, 8));
        return $s1 < $e2 && $s2 < $e1;
    }

    private static function normalizeTime($t)
    {
        $t = trim((string) $t);
        if (preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t . ':00';
        }
        return preg_match('/^\d{2}:\d{2}:\d{2}$/', $t) ? $t : '08:00:00';
    }
}
