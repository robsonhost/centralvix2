<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixPmNotificationService
{
    public static function notifyClient($project, $subject, $body)
    {
        if (CentralVixPmModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $client = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $project->client_id)->first();
        if (!$client || empty($client->email)) {
            return;
        }
        $html = '<div style="font-family:Segoe UI,sans-serif;">'
            . '<h2 style="color:#1e4fd8;">CentralVix Project Manager</h2>'
            . '<p><strong>' . CentralVixPmSecurity::e($project->project_name) . '</strong></p>'
            . $body
            . '</div>';
        try {
            if (function_exists('sendMessage')) {
                sendMessage('CentralVix PM', (int) $project->client_id, [
                    'customsubject' => $subject,
                    'custommessage' => $html,
                ]);
            } else {
                @mail($client->email, $subject, $html, "Content-type: text/html; charset=UTF-8\r\n");
            }
        } catch (Throwable $e) {
            CentralVixPmLogger::log($project->id, 'email_error', $e->getMessage());
        }
    }

    public static function taskUpdated($project, $task)
    {
        if ($task->visibility !== 'client') {
            return;
        }
        self::notifyClient(
            $project,
            '[CentralVix] Atualização de tarefa — ' . $project->project_name,
            '<p>Tarefa: ' . CentralVixPmSecurity::e($task->title) . '</p>'
            . '<p>Status: ' . CentralVixPmSecurity::e(CentralVixPmTaskService::statusLabel($task->status)) . '</p>'
        );
    }

    public static function awaitingClient($project)
    {
        self::notifyClient(
            $project,
            '[CentralVix] Projeto aguardando você — ' . $project->project_name,
            '<p>O projeto está aguardando sua interação.</p>'
        );
    }
}
