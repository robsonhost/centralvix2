<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteProjectService
{
    const STATUSES = [
        'aguardando_briefing' => 'Aguardando briefing',
        'briefing_recebido' => 'Briefing recebido',
        'aguardando_materiais' => 'Aguardando materiais',
        'em_design' => 'Em design',
        'em_desenvolvimento' => 'Em desenvolvimento',
        'em_revisao' => 'Em revisão',
        'aguardando_cliente' => 'Aguardando cliente',
        'alteracoes_solicitadas' => 'Alterações solicitadas',
        'aprovado' => 'Aprovado',
        'publicando' => 'Publicando',
        'finalizado' => 'Finalizado',
    ];

    const STAGE_ORDER = [
        'aguardando_briefing', 'briefing_recebido', 'aguardando_materiais',
        'em_design', 'em_desenvolvimento', 'em_revisao', 'aguardando_cliente',
        'alteracoes_solicitadas', 'aprovado', 'publicando', 'finalizado',
    ];

    public static function statusLabel($status)
    {
        return self::STATUSES[$status] ?? $status;
    }

    public static function nextStageHint($status)
    {
        $map = [
            'aguardando_briefing' => 'Preencha o briefing do projeto',
            'briefing_recebido' => 'Envie logos, fotos e textos',
            'aguardando_materiais' => 'Aguardando envio de materiais',
            'em_design' => 'Equipe trabalhando no design',
            'em_desenvolvimento' => 'Desenvolvimento em andamento',
            'em_revisao' => 'Revisão interna da CentralVix',
            'aguardando_cliente' => 'Sua aprovação ou feedback',
            'alteracoes_solicitadas' => 'Ajustes em andamento',
            'aprovado' => 'Preparando publicação',
            'publicando' => 'Publicação do site',
            'finalizado' => 'Projeto entregue',
        ];
        return $map[$status] ?? 'Acompanhe as atualizações';
    }

    public static function progressForStatus($status)
    {
        $idx = array_search($status, self::STAGE_ORDER, true);
        if ($idx === false) {
            return 0;
        }
        return (int) round(($idx / (count(self::STAGE_ORDER) - 1)) * 100);
    }

    public static function dashboardStats()
    {
        $today = date('Y-m-d');
        $active = Capsule::table('mod_centralvix_site_projects')
            ->whereNotIn('status', ['finalizado'])
            ->count();
        $awaitingClient = Capsule::table('mod_centralvix_site_projects')
            ->whereIn('status', ['aguardando_cliente', 'aguardando_briefing', 'aguardando_materiais'])
            ->count();
        $overdue = Capsule::table('mod_centralvix_site_projects')
            ->whereNotIn('status', ['finalizado', 'aprovado', 'publicando'])
            ->where('deadline', '<', $today)
            ->whereNotNull('deadline')
            ->count();
        $inDev = Capsule::table('mod_centralvix_site_projects')
            ->whereIn('status', ['em_design', 'em_desenvolvimento', 'em_revisao'])
            ->count();
        $approved = Capsule::table('mod_centralvix_site_projects')->where('status', 'aprovado')->count();
        $finished = Capsule::table('mod_centralvix_site_projects')->where('status', 'finalizado')->count();
        return compact('active', 'awaitingClient', 'overdue', 'inDev', 'approved', 'finished');
    }

    public static function listAll($filters = [], $limit = 200)
    {
        $q = Capsule::table('mod_centralvix_site_projects as p')
            ->leftJoin('tblclients as c', 'c.id', '=', 'p.client_id')
            ->select('p.*', Capsule::raw("CONCAT(c.firstname, ' ', c.lastname) as client_name"));
        if (!empty($filters['status'])) {
            $q->where('p.status', $filters['status']);
        }
        if (!empty($filters['client_id'])) {
            $q->where('p.client_id', (int) $filters['client_id']);
        }
        return $q->orderBy('p.id', 'desc')->limit($limit)->get();
    }

    public static function listByClient($clientId)
    {
        return Capsule::table('mod_centralvix_site_projects')
            ->where('client_id', (int) $clientId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_site_projects')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function create(array $data)
    {
        $clientId = (int) ($data['client_id'] ?? 0);
        $name = CentralVixSiteSecurity::sanitizeText($data['project_name'] ?? '', 150);
        if (!$clientId || $name === '') {
            return ['success' => false, 'message' => 'Cliente e nome do projeto são obrigatórios.'];
        }
        $domain = trim($data['domain'] ?? '');
        if ($domain && !CentralVixSiteSecurity::validateDomain($domain)) {
            return ['success' => false, 'message' => 'Domínio inválido.'];
        }
        $status = 'aguardando_briefing';
        $id = Capsule::table('mod_centralvix_site_projects')->insertGetId([
            'client_id' => $clientId,
            'service_id' => (int) ($data['service_id'] ?? 0),
            'product_name' => CentralVixSiteSecurity::sanitizeText($data['product_name'] ?? '', 150),
            'project_name' => $name,
            'domain' => CentralVixSiteSecurity::sanitizeText($domain, 150),
            'plan' => CentralVixSiteSecurity::sanitizeText($data['plan'] ?? '', 100),
            'assigned_admin' => (int) ($data['assigned_admin'] ?? 0),
            'start_date' => $data['start_date'] ?: date('Y-m-d'),
            'deadline' => $data['deadline'] ?: null,
            'progress_percent' => self::progressForStatus($status),
            'status' => $status,
            'current_stage' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Capsule::table('mod_centralvix_site_briefings')->insert([
            'project_id' => $id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $project = self::get($id);
        CentralVixSiteTimelineService::add($id, 'project_created', 'Projeto criado', $name, 'admin', (int) ($_SESSION['adminid'] ?? 0), CentralVixSiteModule::adminName($_SESSION['adminid'] ?? 0));
        CentralVixSiteNotificationService::notify('project_created', $project);
        return ['success' => true, 'id' => $id];
    }

    public static function update($id, array $data)
    {
        $project = self::get($id);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $row = [
            'project_name' => CentralVixSiteSecurity::sanitizeText($data['project_name'] ?? $project->project_name, 150),
            'product_name' => CentralVixSiteSecurity::sanitizeText($data['product_name'] ?? $project->product_name, 150),
            'domain' => CentralVixSiteSecurity::sanitizeText($data['domain'] ?? $project->domain, 150),
            'plan' => CentralVixSiteSecurity::sanitizeText($data['plan'] ?? $project->plan, 100),
            'assigned_admin' => (int) ($data['assigned_admin'] ?? $project->assigned_admin),
            'start_date' => $data['start_date'] ?? $project->start_date,
            'deadline' => $data['deadline'] ?? $project->deadline,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (isset($data['progress_percent'])) {
            $row['progress_percent'] = max(0, min(100, (int) $data['progress_percent']));
        }
        Capsule::table('mod_centralvix_site_projects')->where('id', $id)->update($row);
        return ['success' => true];
    }

    public static function changeStatus($id, $newStatus, $actorType = 'admin', $actorId = 0, $actorName = 'Sistema', $note = '')
    {
        if (!isset(self::STATUSES[$newStatus])) {
            return ['success' => false, 'message' => 'Status inválido.'];
        }
        $project = self::get($id);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $old = $project->status;
        if ($old === $newStatus) {
            return ['success' => true, 'unchanged' => true];
        }
        Capsule::table('mod_centralvix_site_projects')->where('id', $id)->update([
            'status' => $newStatus,
            'current_stage' => $newStatus,
            'progress_percent' => self::progressForStatus($newStatus),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $label = self::statusLabel($newStatus);
        CentralVixSiteTimelineService::add(
            $id,
            'status_change',
            'Status alterado para ' . $label,
            $note ?: ('De ' . self::statusLabel($old) . ' para ' . $label),
            $actorType,
            $actorId,
            $actorName,
            ['from' => $old, 'to' => $newStatus]
        );
        $project = self::get($id);
        self::notifyStatusChange($project, $newStatus, $note);
        return ['success' => true, 'status' => $newStatus];
    }

    protected static function notifyStatusChange($project, $status, $note = '')
    {
        $map = [
            'aguardando_materiais' => 'material_request',
            'aguardando_cliente' => 'awaiting_approval',
            'alteracoes_solicitadas' => 'change_requested',
            'aprovado' => 'project_approved',
            'finalizado' => 'project_completed',
            'em_revisao' => 'new_version',
        ];
        if (isset($map[$status])) {
            CentralVixSiteNotificationService::notify($map[$status], $project, ['message' => $note]);
        }
    }

    public static function isOverdue($project)
    {
        if (empty($project->deadline) || $project->status === 'finalizado') {
            return false;
        }
        return $project->deadline < date('Y-m-d');
    }
}
