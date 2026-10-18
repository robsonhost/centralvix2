<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusIncidentService
{
    public static function listIncidents($activeOnly = false, $limit = 50)
    {
        $q = Capsule::table('mod_centralvix_status_incidents')->orderBy('started_at', 'desc');
        if ($activeOnly) {
            $q->whereNotIn('status', ['resolved']);
        }
        return $q->limit($limit)->get();
    }

    public static function getIncident($id)
    {
        return Capsule::table('mod_centralvix_status_incidents')->where('id', (int) $id)->first();
    }

    public static function getUpdates($incidentId)
    {
        return Capsule::table('mod_centralvix_status_incident_updates')
            ->where('incident_id', (int) $incidentId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public static function saveIncident(array $data, $id = 0)
    {
        $row = [
            'title' => CentralVixStatusSecurity::sanitizeText($data['title'] ?? '', 200),
            'description' => CentralVixStatusSecurity::sanitizeText($data['description'] ?? '', 2000),
            'severity' => preg_replace('/[^a-z_]/', '', $data['severity'] ?? 'partial_outage'),
            'affected_services' => json_encode(array_map('intval', (array) ($data['affected_services'] ?? []))),
            'status' => preg_replace('/[^a-z_]/', '', $data['status'] ?? 'investigating'),
            'started_at' => $data['started_at'] ?? date('Y-m-d H:i:s'),
            'resolved_at' => !empty($data['resolved_at']) ? $data['resolved_at'] : null,
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            Capsule::table('mod_centralvix_status_incidents')->where('id', (int) $id)->update($row);
            return (int) $id;
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = (int) Capsule::table('mod_centralvix_status_incidents')->insertGetId($row);
        self::addUpdate($newId, $row['status'], 'Incidente criado.');
        CentralVixStatusNotificationService::notifyIncidentCreated(self::getIncident($newId));
        return $newId;
    }

    public static function addUpdate($incidentId, $status, $message)
    {
        Capsule::table('mod_centralvix_status_incident_updates')->insert([
            'incident_id' => (int) $incidentId,
            'status' => preg_replace('/[^a-z_]/', '', $status),
            'message' => CentralVixStatusSecurity::sanitizeText($message, 2000),
            'admin_id' => (int) ($_SESSION['adminid'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $update = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        if ($status === 'resolved') {
            $update['resolved_at'] = date('Y-m-d H:i:s');
        }
        Capsule::table('mod_centralvix_status_incidents')->where('id', (int) $incidentId)->update($update);
    }

    public static function recentPublic($limit = 10)
    {
        return Capsule::table('mod_centralvix_status_incidents')
            ->where('is_public', 1)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
