<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteBriefingService
{
    const STANDARD_FIELDS = [
        'company_name' => 'Nome empresa',
        'segment' => 'Segmento',
        'description' => 'Descrição',
        'objective' => 'Objetivo',
        'audience' => 'Público',
        'pages' => 'Páginas',
        'colors' => 'Cores',
        'reference_sites' => 'Sites referência',
        'social_networks' => 'Redes sociais',
        'contact_info' => 'Contato',
        'notes' => 'Observações',
    ];

    public static function getByProject($projectId)
    {
        return Capsule::table('mod_centralvix_site_briefings')->where('project_id', (int) $projectId)->first();
    }

    public static function getCustomFieldsConfig()
    {
        $raw = CentralVixSiteModule::dbSetting('briefing_custom_fields', '[]');
        $fields = json_decode($raw, true);
        return is_array($fields) ? $fields : [];
    }

    public static function save($projectId, array $data, $clientId = null)
    {
        $project = CentralVixSiteProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $briefing = self::getByProject($projectId);
        if (!$briefing) {
            Capsule::table('mod_centralvix_site_briefings')->insert(['project_id' => $projectId, 'created_at' => date('Y-m-d H:i:s')]);
            $briefing = self::getByProject($projectId);
        }

        $row = ['updated_at' => date('Y-m-d H:i:s')];
        foreach (array_keys(self::STANDARD_FIELDS) as $field) {
            $row[$field] = CentralVixSiteSecurity::sanitizeText($data[$field] ?? '', 5000);
        }
        $custom = [];
        $config = self::getCustomFieldsConfig();
        foreach ($config as $cf) {
            $key = $cf['key'] ?? '';
            if ($key) {
                $custom[$key] = CentralVixSiteSecurity::sanitizeText($data['custom'][$key] ?? '', 3000);
            }
        }
        $row['custom_fields_json'] = json_encode($custom, JSON_UNESCAPED_UNICODE);
        if (empty($briefing->submitted_at) && !empty($data['submit'])) {
            $row['submitted_at'] = date('Y-m-d H:i:s');
        }

        Capsule::table('mod_centralvix_site_briefings')->where('project_id', $projectId)->update($row);

        if (!empty($data['submit'])) {
            CentralVixSiteProjectService::changeStatus(
                $projectId,
                'briefing_recebido',
                $clientId ? 'client' : 'admin',
                $clientId ? (int) $clientId : (int) ($_SESSION['adminid'] ?? 0),
                $clientId ? CentralVixSiteModule::clientName($clientId) : CentralVixSiteModule::adminName($_SESSION['adminid'] ?? 0),
                'Briefing enviado pelo cliente'
            );
            CentralVixSiteTimelineService::add(
                $projectId,
                'briefing_submitted',
                'Briefing recebido',
                'Formulário de briefing preenchido',
                $clientId ? 'client' : 'admin',
                $clientId ? (int) $clientId : (int) ($_SESSION['adminid'] ?? 0),
                $clientId ? CentralVixSiteModule::clientName($clientId) : CentralVixSiteModule::adminName($_SESSION['adminid'] ?? 0)
            );
        }

        return ['success' => true];
    }

    public static function toArray($briefing)
    {
        if (!$briefing) {
            return array_fill_keys(array_keys(self::STANDARD_FIELDS), '');
        }
        $out = [];
        foreach (array_keys(self::STANDARD_FIELDS) as $field) {
            $out[$field] = $briefing->$field ?? '';
        }
        $out['custom'] = json_decode($briefing->custom_fields_json ?? '{}', true) ?: [];
        $out['submitted_at'] = $briefing->submitted_at ?? null;
        return $out;
    }
}
