<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmProjectService
{
    const TYPES = [
        'sites' => 'Sites',
        'apps' => 'Aplicativos',
        'systems' => 'Sistemas',
        'integrations' => 'Integrações',
        'infrastructure' => 'Infraestrutura',
        'streaming' => 'Streaming',
        'custom' => 'Serviços personalizados',
    ];

    const STATUSES = [
        'planning' => 'Planejamento',
        'in_progress' => 'Em andamento',
        'awaiting_client' => 'Aguardando cliente',
        'paused' => 'Pausado',
        'in_review' => 'Em revisão',
        'completed' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];

    public static function statusLabel($s)
    {
        return self::STATUSES[$s] ?? $s;
    }

    public static function typeLabel($t)
    {
        return self::TYPES[$t] ?? $t;
    }

    public static function dashboardStats()
    {
        $today = date('Y-m-d');
        $active = Capsule::table('mod_centralvix_pm_projects')->whereNotIn('status', ['completed', 'cancelled'])->count();
        $overdue = Capsule::table('mod_centralvix_pm_projects')
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('deadline', '<', $today)->whereNotNull('deadline')->count();
        $openTasks = Capsule::table('mod_centralvix_pm_tasks')->where('status', '!=', 'done')->count();
        $awaiting = Capsule::table('mod_centralvix_pm_projects')->where('status', 'awaiting_client')->count();
        $completed = Capsule::table('mod_centralvix_pm_projects')->where('status', 'completed')->count();
        $hours = (float) Capsule::table('mod_centralvix_pm_time_entries')->sum('hours');
        return compact('active', 'overdue', 'openTasks', 'awaiting', 'completed', 'hours');
    }

    public static function listAll($filters = [], $limit = 200)
    {
        $q = Capsule::table('mod_centralvix_pm_projects as p')
            ->leftJoin('tblclients as c', 'c.id', '=', 'p.client_id')
            ->select('p.*', Capsule::raw("CONCAT(c.firstname,' ',c.lastname) as client_name"));
        if (!empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $q->where('p.project_type', $filters['type']);
        }
        return $q->orderBy('p.id', 'desc')->limit($limit)->get();
    }

    public static function listByClient($clientId, $clientVisibleOnly = true)
    {
        $q = Capsule::table('mod_centralvix_pm_projects')->where('client_id', (int) $clientId);
        if ($clientVisibleOnly) {
            $q->where('client_visible', 1);
        }
        return $q->orderBy('id', 'desc')->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_pm_projects')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId)->where('client_visible', 1);
        }
        return $q->first();
    }

    public static function create(array $data)
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        $name = CentralVixPmSecurity::sanitizeText($data['project_name'] ?? '', 150);
        if (!$clientId || $name === '') {
            return ['success' => false, 'message' => 'Cliente e nome são obrigatórios.'];
        }
        $type = isset(self::TYPES[$data['project_type'] ?? '']) ? $data['project_type'] : 'custom';
        $status = isset(self::STATUSES[$data['status'] ?? '']) ? $data['status'] : 'planning';
        $id = Capsule::table('mod_centralvix_pm_projects')->insertGetId([
            'client_id' => $clientId,
            'service_id' => (int) ($data['service_id'] ?? 0),
            'invoice_id' => (int) ($data['invoice_id'] ?? 0),
            'ticket_id' => (int) ($data['ticket_id'] ?? 0),
            'project_name' => $name,
            'project_type' => $type,
            'description' => CentralVixPmSecurity::sanitizeText($data['description'] ?? '', 5000),
            'status' => $status,
            'progress_percent' => (int) ($data['progress_percent'] ?? 0),
            'assigned_admin' => (int) ($data['assigned_admin'] ?? 0),
            'start_date' => $data['start_date'] ?: null,
            'deadline' => $data['deadline'] ?: null,
            'client_visible' => !empty($data['client_visible']) ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixPmLogger::log($id, 'project_created', 'Projeto criado: ' . $name, null, 'admin', (int) ($_SESSION['adminid'] ?? 0));
        return ['success' => true, 'id' => $id];
    }

    public static function update($id, array $data)
    {
        $p = self::get($id);
        if (!$p) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $row = [
            'project_name' => CentralVixPmSecurity::sanitizeText($data['project_name'] ?? $p->project_name, 150),
            'project_type' => isset(self::TYPES[$data['project_type'] ?? '']) ? $data['project_type'] : $p->project_type,
            'description' => CentralVixPmSecurity::sanitizeText($data['description'] ?? $p->description, 5000),
            'service_id' => (int) ($data['service_id'] ?? $p->service_id),
            'invoice_id' => (int) ($data['invoice_id'] ?? $p->invoice_id),
            'ticket_id' => (int) ($data['ticket_id'] ?? $p->ticket_id),
            'assigned_admin' => (int) ($data['assigned_admin'] ?? $p->assigned_admin),
            'start_date' => $data['start_date'] ?? $p->start_date,
            'deadline' => $data['deadline'] ?? $p->deadline,
            'progress_percent' => max(0, min(100, (int) ($data['progress_percent'] ?? $p->progress_percent))),
            'client_visible' => isset($data['client_visible']) ? (!empty($data['client_visible']) ? 1 : 0) : $p->client_visible,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        Capsule::table('mod_centralvix_pm_projects')->where('id', $id)->update($row);
        return ['success' => true];
    }

    public static function changeStatus($id, $status, $actorType = 'admin', $actorId = 0)
    {
        if (!isset(self::STATUSES[$status])) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }
        $p = self::get($id);
        if (!$p) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        Capsule::table('mod_centralvix_pm_projects')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixPmLogger::log($id, 'status_change', 'Status: ' . self::statusLabel($status), null, $actorType, $actorId);
        $p = self::get($id);
        if ($status === 'awaiting_client' && $p->client_visible) {
            CentralVixPmNotificationService::awaitingClient($p);
        }
        return ['success' => true];
    }

    public static function recalcProgress($projectId)
    {
        $tasks = Capsule::table('mod_centralvix_pm_tasks')->where('project_id', (int) $projectId)->where('parent_id', 0)->get();
        if ($tasks->isEmpty()) {
            return;
        }
        $total = 0;
        $count = 0;
        foreach ($tasks as $t) {
            $total += (int) $t->progress_percent;
            $count++;
        }
        $pct = $count ? (int) round($total / $count) : 0;
        Capsule::table('mod_centralvix_pm_projects')->where('id', (int) $projectId)->update([
            'progress_percent' => $pct,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function isOverdue($p)
    {
        if (empty($p->deadline) || in_array($p->status, ['completed', 'cancelled'], true)) {
            return false;
        }
        return $p->deadline < date('Y-m-d');
    }
}
