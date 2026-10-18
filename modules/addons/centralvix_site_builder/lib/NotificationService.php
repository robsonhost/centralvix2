<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteNotificationService
{
    const EVENTS = [
        'project_created' => 'Projeto criado',
        'material_request' => 'Solicitação de material',
        'new_version' => 'Nova versão disponível',
        'awaiting_approval' => 'Aguardando aprovação',
        'change_requested' => 'Alteração solicitada',
        'project_approved' => 'Projeto aprovado',
        'project_completed' => 'Projeto concluído',
    ];

    public static function notify($eventKey, $project, array $extra = [])
    {
        if (CentralVixSiteModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $client = Capsule::table('tblclients')->where('id', (int) $project->client_id)->first();
        if (!$client || empty($client->email)) {
            return;
        }

        $subject = self::subject($eventKey, $project);
        $body = self::body($eventKey, $project, $extra);

        try {
            if (function_exists('sendMessage')) {
                sendMessage('CentralVix Site Builder', (int) $project->client_id, [
                    'customsubject' => $subject,
                    'custommessage' => $body,
                ]);
            } else {
                $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
                @mail($client->email, $subject, $body, $headers);
            }
        } catch (Throwable $e) {
            CentralVixSiteLogger::error('Falha e-mail: ' . $e->getMessage(), ['event' => $eventKey, 'project_id' => $project->id]);
        }

        self::prepareWhatsApp($eventKey, $project, $client, $extra);
    }

    protected static function subject($eventKey, $project)
    {
        $label = self::EVENTS[$eventKey] ?? 'Atualização do projeto';
        return '[CentralVix] ' . $label . ' — ' . $project->project_name;
    }

    protected static function body($eventKey, $project, array $extra)
    {
        $status = CentralVixSiteProjectService::statusLabel($project->status);
        $msg = $extra['message'] ?? '';
        $link = self::clientProjectUrl($project->id);
        return '<div style="font-family:Segoe UI,sans-serif;color:#0f172a;">'
            . '<h2 style="color:#1e4fd8;">CentralVix Site Builder</h2>'
            . '<p><strong>Projeto:</strong> ' . CentralVixSiteSecurity::e($project->project_name) . '</p>'
            . '<p><strong>Status:</strong> ' . CentralVixSiteSecurity::e($status) . '</p>'
            . ($msg ? '<p>' . nl2br(CentralVixSiteSecurity::e($msg)) . '</p>' : '')
            . '<p><a href="' . CentralVixSiteSecurity::e($link) . '" style="background:#1e4fd8;color:#fff;padding:10px 18px;text-decoration:none;border-radius:8px;">Acessar projeto</a></p>'
            . '</div>';
    }

    protected static function clientProjectUrl($projectId)
    {
        $base = rtrim($GLOBALS['CONFIG']['SystemURL'] ?? '', '/');
        return $base . '/index.php?m=centralvix_site_builder&action=project&id=' . (int) $projectId;
    }

    protected static function prepareWhatsApp($eventKey, $project, $client, array $extra)
    {
        if (CentralVixSiteModule::dbSetting('whatsapp_enabled', '') !== '1') {
            return;
        }
        $phone = preg_replace('/\D/', '', $client->phonenumber ?? '');
        if ($phone === '') {
            return;
        }
        $text = self::EVENTS[$eventKey] ?? 'Atualização';
        $text .= ' — ' . $project->project_name;
        if (!empty($extra['message'])) {
            $text .= ': ' . mb_substr($extra['message'], 0, 120);
        }
        CentralVixSiteLogger::info('WhatsApp preparado', [
            'phone' => $phone,
            'event' => $eventKey,
            'project_id' => $project->id,
            'message' => $text,
        ]);
    }
}
