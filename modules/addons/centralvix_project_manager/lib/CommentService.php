<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmCommentService
{
    public static function listByProject($projectId, $taskId = null, $clientView = false)
    {
        $q = Capsule::table('mod_centralvix_pm_comments')->where('project_id', (int) $projectId);
        if ($taskId) {
            $q->where('task_id', (int) $taskId);
        }
        if ($clientView) {
            $q->where('visibility', 'public');
        }
        return $q->orderBy('id', 'asc')->get();
    }

    public static function add($projectId, $taskId, $authorType, $authorId, $authorName, $message, $visibility = 'internal', $clientId = null)
    {
        $project = CentralVixPmProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        if ($clientId !== null) {
            $visibility = 'public';
        }
        $msg = CentralVixPmSecurity::sanitizeText($message, 3000);
        if ($msg === '') {
            return ['success' => false, 'message' => 'Comentário vazio.'];
        }
        $vis = $visibility === 'public' ? 'public' : 'internal';
        $id = Capsule::table('mod_centralvix_pm_comments')->insertGetId([
            'project_id' => (int) $projectId,
            'task_id' => $taskId ? (int) $taskId : null,
            'author_type' => $authorType,
            'author_id' => (int) $authorId,
            'author_name' => CentralVixPmSecurity::sanitizeText($authorName, 150),
            'message' => $msg,
            'visibility' => $vis,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixPmLogger::log($projectId, 'comment', 'Comentário adicionado', $taskId, $authorType, $authorId);
        return ['success' => true, 'id' => $id];
    }
}
