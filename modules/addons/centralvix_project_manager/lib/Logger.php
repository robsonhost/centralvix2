<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixPmLogger
{
    public static function log($projectId, $eventType, $message, $taskId = null, $actorType = 'system', $actorId = 0, array $meta = [])
    {
        WHMCS\Database\Capsule::table('mod_centralvix_pm_logs')->insert([
            'project_id' => (int) $projectId,
            'task_id' => $taskId ? (int) $taskId : null,
            'event_type' => $eventType,
            'message' => CentralVixPmSecurity::sanitizeText($message, 2000),
            'actor_type' => $actorType,
            'actor_id' => (int) $actorId,
            'meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $dir = CentralVixPmModule::path() . '/storage/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $dir . '/pm.log',
            sprintf("[%s] P%d T%s %s\n", date('Y-m-d H:i:s'), $projectId, $taskId ?: '-', $message),
            FILE_APPEND | LOCK_EX
        );
    }

    public static function listByProject($projectId, $limit = 50)
    {
        return WHMCS\Database\Capsule::table('mod_centralvix_pm_logs')
            ->where('project_id', (int) $projectId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
