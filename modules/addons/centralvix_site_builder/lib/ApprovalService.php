<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteApprovalService
{
    public static function listByProject($projectId)
    {
        return Capsule::table('mod_centralvix_site_approvals')
            ->where('project_id', (int) $projectId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function approve($projectId, $clientId, $stage, $message = '')
    {
        $project = CentralVixSiteProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        if (!in_array($project->status, ['aguardando_cliente', 'em_revisao'], true)) {
            return ['success' => false, 'message' => 'Este projeto não está aguardando sua aprovação.'];
        }
        $stage = CentralVixSiteSecurity::sanitizeText($stage ?: $project->current_stage, 50);
        $msg = CentralVixSiteSecurity::sanitizeText($message, 2000);
        $ip = CentralVixSiteSecurity::clientIp();

        Capsule::table('mod_centralvix_site_approvals')->insert([
            'project_id' => (int) $projectId,
            'client_id' => (int) $clientId,
            'stage' => $stage,
            'action' => 'approve',
            'message' => $msg,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixSiteTimelineService::add(
            $projectId,
            'approval',
            'Etapa aprovada pelo cliente',
            $msg ?: 'Cliente aprovou a etapa ' . CentralVixSiteProjectService::statusLabel($stage),
            'client',
            $clientId,
            CentralVixSiteModule::clientName($clientId),
            ['stage' => $stage, 'ip' => $ip]
        );

        CentralVixSiteProjectService::changeStatus(
            $projectId,
            'aprovado',
            'client',
            $clientId,
            CentralVixSiteModule::clientName($clientId),
            'Aprovado pelo cliente'
        );

        return ['success' => true];
    }

    public static function requestChanges($projectId, $clientId, $stage, $message)
    {
        $project = CentralVixSiteProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $msg = CentralVixSiteSecurity::sanitizeText($message, 2000);
        if ($msg === '') {
            return ['success' => false, 'message' => 'Descreva as alterações desejadas.'];
        }
        $stage = CentralVixSiteSecurity::sanitizeText($stage ?: $project->current_stage, 50);
        $ip = CentralVixSiteSecurity::clientIp();

        Capsule::table('mod_centralvix_site_approvals')->insert([
            'project_id' => (int) $projectId,
            'client_id' => (int) $clientId,
            'stage' => $stage,
            'action' => 'request_change',
            'message' => $msg,
            'ip_address' => $ip,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixSiteTimelineService::add(
            $projectId,
            'change_request',
            'Alteração solicitada',
            $msg,
            'client',
            $clientId,
            CentralVixSiteModule::clientName($clientId),
            ['stage' => $stage, 'ip' => $ip]
        );

        CentralVixSiteProjectService::changeStatus(
            $projectId,
            'alteracoes_solicitadas',
            'client',
            $clientId,
            CentralVixSiteModule::clientName($clientId),
            $msg
        );

        return ['success' => true];
    }

    public static function formatRecord($row)
    {
        return [
            'id' => (int) $row->id,
            'stage' => $row->stage,
            'stage_label' => CentralVixSiteProjectService::statusLabel($row->stage),
            'action' => $row->action,
            'action_label' => $row->action === 'approve' ? 'Aprovado' : 'Alteração solicitada',
            'message' => $row->message,
            'ip_address' => $row->ip_address,
            'created_at' => $row->created_at,
            'date' => date('d/m/Y', strtotime($row->created_at)),
            'time' => date('H:i:s', strtotime($row->created_at)),
        ];
    }
}
