<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmMilestoneService
{
    public static function listByProject($projectId, $clientView = false)
    {
        $q = Capsule::table('mod_centralvix_pm_milestones')->where('project_id', (int) $projectId)->orderBy('sort_order')->orderBy('id');
        if ($clientView) {
            $q->where('client_visible', 1);
        }
        return $q->get();
    }

    public static function save(array $data, $id = 0)
    {
        $projectId = (int) ($data['project_id'] ?? 0);
        if (!CentralVixPmProjectService::get($projectId)) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $title = CentralVixPmSecurity::sanitizeText($data['title'] ?? '', 150);
        if ($title === '') {
            return ['success' => false, 'message' => 'Título obrigatório.'];
        }
        $row = [
            'project_id' => $projectId,
            'title' => $title,
            'description' => CentralVixPmSecurity::sanitizeText($data['description'] ?? '', 2000),
            'due_date' => $data['due_date'] ?: null,
            'client_visible' => !empty($data['client_visible']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['completed'])) {
            $row['completed_at'] = date('Y-m-d H:i:s');
        }
        if ($id > 0) {
            Capsule::table('mod_centralvix_pm_milestones')->where('id', $id)->where('project_id', $projectId)->update($row);
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_pm_milestones')->insertGetId($row);
        }
        CentralVixPmLogger::log($projectId, 'milestone', 'Marco: ' . $title);
        return ['success' => true, 'id' => $id];
    }
}
