<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmTimeService
{
    public static function listByProject($projectId, $taskId = null)
    {
        $q = Capsule::table('mod_centralvix_pm_time_entries')->where('project_id', (int) $projectId);
        if ($taskId) {
            $q->where('task_id', (int) $taskId);
        }
        return $q->orderBy('entry_date', 'desc')->get();
    }

    public static function totalHours($projectId)
    {
        return (float) Capsule::table('mod_centralvix_pm_time_entries')->where('project_id', (int) $projectId)->sum('hours');
    }

    public static function add(array $data)
    {
        $projectId = (int) ($data['project_id'] ?? 0);
        if (!CentralVixPmProjectService::get($projectId)) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $hours = max(0, (float) ($data['hours'] ?? 0));
        if ($hours <= 0) {
            return ['success' => false, 'message' => 'Informe horas válidas.'];
        }
        $taskId = (int) ($data['task_id'] ?? 0);
        $id = Capsule::table('mod_centralvix_pm_time_entries')->insertGetId([
            'project_id' => $projectId,
            'task_id' => $taskId ?: null,
            'admin_id' => (int) ($_SESSION['adminid'] ?? 0),
            'hours' => $hours,
            'description' => CentralVixPmSecurity::sanitizeText($data['description'] ?? '', 500),
            'entry_date' => $data['entry_date'] ?: date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($taskId) {
            $used = (float) Capsule::table('mod_centralvix_pm_time_entries')->where('task_id', $taskId)->sum('hours');
            Capsule::table('mod_centralvix_pm_tasks')->where('id', $taskId)->update(['used_hours' => $used]);
        }
        CentralVixPmLogger::log($projectId, 'time_entry', $hours . 'h registradas', $taskId ?: null, 'admin', (int) ($_SESSION['adminid'] ?? 0));
        return ['success' => true, 'id' => $id];
    }
}
