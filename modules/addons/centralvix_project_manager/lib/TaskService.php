<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmTaskService
{
    const PRIORITIES = ['low' => 'Baixa', 'medium' => 'Média', 'high' => 'Alta', 'urgent' => 'Urgente'];
    const STATUSES = ['todo' => 'A fazer', 'in_progress' => 'Em andamento', 'review' => 'Revisão', 'done' => 'Concluído'];
    const KANBAN_COLUMNS = ['todo', 'in_progress', 'review', 'done'];

    public static function priorityLabel($p)
    {
        return self::PRIORITIES[$p] ?? $p;
    }

    public static function statusLabel($s)
    {
        return self::STATUSES[$s] ?? $s;
    }

    public static function listByProject($projectId, $clientView = false)
    {
        $q = Capsule::table('mod_centralvix_pm_tasks')->where('project_id', (int) $projectId)->orderBy('kanban_order')->orderBy('id');
        if ($clientView) {
            $q->where('visibility', 'client');
        }
        return $q->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_pm_tasks as t')
            ->join('mod_centralvix_pm_projects as p', 'p.id', '=', 't.project_id')
            ->where('t.id', (int) $id)
            ->select('t.*', 'p.client_id', 'p.client_visible');
        if ($clientId !== null) {
            $q->where('p.client_id', (int) $clientId)->where('p.client_visible', 1)->where('t.visibility', 'client');
        }
        return $q->first();
    }

    public static function subtasks($parentId)
    {
        return Capsule::table('mod_centralvix_pm_tasks')->where('parent_id', (int) $parentId)->orderBy('id')->get();
    }

    public static function kanbanBoard($projectId, $clientView = false)
    {
        $tasks = self::listByProject($projectId, $clientView);
        $board = [];
        foreach (self::KANBAN_COLUMNS as $col) {
            $board[$col] = [];
        }
        foreach ($tasks as $t) {
            if ((int) $t->parent_id !== 0) {
                continue;
            }
            $col = in_array($t->status, self::KANBAN_COLUMNS, true) ? $t->status : 'todo';
            $board[$col][] = $t;
        }
        foreach ($board as $col => &$items) {
            usort($items, function ($a, $b) {
                return (int) $a->kanban_order <=> (int) $b->kanban_order;
            });
        }
        return $board;
    }

    public static function save(array $data, $taskId = 0)
    {
        $projectId = (int) ($data['project_id'] ?? 0);
        $project = CentralVixPmProjectService::get($projectId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $title = CentralVixPmSecurity::sanitizeText($data['title'] ?? '', 200);
        if ($title === '') {
            return ['success' => false, 'message' => 'Título obrigatório.'];
        }
        $status = isset(self::STATUSES[$data['status'] ?? '']) ? $data['status'] : 'todo';
        $priority = isset(self::PRIORITIES[$data['priority'] ?? '']) ? $data['priority'] : 'medium';
        $visibility = ($data['visibility'] ?? 'internal') === 'client' ? 'client' : 'internal';
        $parentId = (int) ($data['parent_id'] ?? 0);

        $row = [
            'project_id' => $projectId,
            'parent_id' => $parentId,
            'title' => $title,
            'description' => CentralVixPmSecurity::sanitizeText($data['description'] ?? '', 5000),
            'assigned_type' => ($data['assigned_type'] ?? 'admin') === 'client' ? 'client' : 'admin',
            'assigned_id' => (int) ($data['assigned_id'] ?? 0),
            'priority' => $priority,
            'status' => $status,
            'start_date' => $data['start_date'] ?: null,
            'due_date' => $data['due_date'] ?: null,
            'progress_percent' => max(0, min(100, (int) ($data['progress_percent'] ?? 0))),
            'estimated_hours' => max(0, (float) ($data['estimated_hours'] ?? 0)),
            'used_hours' => max(0, (float) ($data['used_hours'] ?? 0)),
            'visibility' => $visibility,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($taskId > 0) {
            $existing = Capsule::table('mod_centralvix_pm_tasks')->where('id', $taskId)->where('project_id', $projectId)->first();
            if (!$existing) {
                return ['success' => false, 'message' => 'Tarefa não encontrada.'];
            }
            Capsule::table('mod_centralvix_pm_tasks')->where('id', $taskId)->update($row);
            $id = $taskId;
            CentralVixPmLogger::log($projectId, 'task_updated', 'Tarefa atualizada: ' . $title, $id, 'admin', (int) ($_SESSION['adminid'] ?? 0));
        } else {
            $maxOrder = (int) Capsule::table('mod_centralvix_pm_tasks')->where('project_id', $projectId)->where('status', $status)->max('kanban_order');
            $row['kanban_order'] = $maxOrder + 1;
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_pm_tasks')->insertGetId($row);
            CentralVixPmLogger::log($projectId, 'task_created', 'Tarefa criada: ' . $title, $id, 'admin', (int) ($_SESSION['adminid'] ?? 0));
        }

        CentralVixPmProjectService::recalcProgress($projectId);
        $task = Capsule::table('mod_centralvix_pm_tasks')->where('id', $id)->first();
        if ($task->visibility === 'client') {
            CentralVixPmNotificationService::taskUpdated($project, $task);
        }
        return ['success' => true, 'id' => $id];
    }

    public static function moveKanban($taskId, $newStatus, $newOrder, $projectId)
    {
        if (!in_array($newStatus, self::KANBAN_COLUMNS, true)) {
            return ['success' => false, 'message' => 'Coluna inválida.'];
        }
        $task = Capsule::table('mod_centralvix_pm_tasks')->where('id', (int) $taskId)->where('project_id', (int) $projectId)->first();
        if (!$task || (int) $task->parent_id !== 0) {
            return ['success' => false, 'message' => 'Tarefa inválida.'];
        }
        $progress = $task->progress_percent;
        if ($newStatus === 'done') {
            $progress = 100;
        }
        Capsule::table('mod_centralvix_pm_tasks')->where('id', $taskId)->update([
            'status' => $newStatus,
            'kanban_order' => (int) $newOrder,
            'progress_percent' => $progress,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixPmLogger::log($projectId, 'kanban_move', 'Tarefa movida para ' . self::statusLabel($newStatus), $taskId, 'admin', (int) ($_SESSION['adminid'] ?? 0));
        CentralVixPmProjectService::recalcProgress($projectId);
        return ['success' => true];
    }

    public static function reorderKanban($projectId, array $columns)
    {
        foreach ($columns as $status => $ids) {
            if (!in_array($status, self::KANBAN_COLUMNS, true) || !is_array($ids)) {
                continue;
            }
            foreach ($ids as $order => $taskId) {
                Capsule::table('mod_centralvix_pm_tasks')
                    ->where('id', (int) $taskId)
                    ->where('project_id', (int) $projectId)
                    ->update(['status' => $status, 'kanban_order' => (int) $order, 'updated_at' => date('Y-m-d H:i:s')]);
            }
        }
        CentralVixPmProjectService::recalcProgress($projectId);
        return ['success' => true];
    }

    public static function delete($id, $projectId)
    {
        Capsule::table('mod_centralvix_pm_tasks')->where('parent_id', (int) $id)->delete();
        Capsule::table('mod_centralvix_pm_tasks')->where('id', (int) $id)->where('project_id', (int) $projectId)->delete();
        CentralVixPmProjectService::recalcProgress($projectId);
        return ['success' => true];
    }
}
