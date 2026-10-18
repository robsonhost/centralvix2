<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigNotificationService
{
    public static function notify($event, $request)
    {
        if (CentralVixMigModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $client = Capsule::table('tblclients')->where('id', (int) $request->client_id)->first();
        if (!$client || empty($client->email)) {
            return;
        }

        $templates = self::templates();
        if (!isset($templates[$event])) {
            return;
        }
        $tpl = $templates[$event];
        $vars = self::vars($request, $client);
        $subject = self::replace($tpl['subject'], $vars);
        $body = self::replace($tpl['body'], $vars);

        if (function_exists('sendMail')) {
            sendMail($client->email, $subject, $body);
        } elseif (function_exists('localAPI')) {
            localAPI('SendEmail', [
                'messagename' => 'General Notification',
                'id' => $client->id,
                'customsubject' => $subject,
                'custommessage' => nl2br($body),
            ]);
        }

        CentralVixMigLogger::log($request->id, 'notify_email', $event, ['email' => $client->email]);
        self::prepareWhatsApp($event, $request, $client, $vars);
    }

    protected static function templates()
    {
        return [
            'request_received' => [
                'subject' => 'Migração recebida — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'Recebemos sua solicitação de migração para o domínio {$domain}.' . "\n" . 'Referência: {$reference}' . "\n\n" . 'Nossa equipe iniciará a análise em breve.' . "\n\n" . 'CentralVix',
            ],
            'info_needed' => [
                'subject' => 'Informações necessárias — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'Precisamos de informações adicionais para continuar a migração de {$domain}.' . "\n\n" . 'Acesse sua área do cliente para responder.' . "\n\n" . 'CentralVix',
            ],
            'scheduled' => [
                'subject' => 'Migração agendada — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'Sua migração foi agendada para {$scheduled_date}.' . "\n" . 'Domínio: {$domain}' . "\n\n" . 'CentralVix',
            ],
            'started' => [
                'subject' => 'Migração iniciada — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'Iniciamos o processo de migração do domínio {$domain}.' . "\n" . 'Acompanhe o progresso na área do cliente.' . "\n\n" . 'CentralVix',
            ],
            'dns_change' => [
                'subject' => 'Alteração de DNS — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'A migração de {$domain} está na fase de DNS.' . "\n" . 'Siga as orientações enviadas pelo suporte para apontar seu domínio.' . "\n\n" . 'CentralVix',
            ],
            'completed' => [
                'subject' => 'Migração concluída — {$reference}',
                'body' => 'Olá {$client_name},' . "\n\n" . 'A migração do domínio {$domain} foi concluída com sucesso.' . "\n" . 'Referência: {$reference}' . "\n\n" . 'CentralVix',
            ],
        ];
    }

    protected static function vars($request, $client)
    {
        return [
            'client_name' => trim($client->firstname . ' ' . $client->lastname),
            'domain' => $request->domain,
            'reference' => $request->reference,
            'scheduled_date' => $request->scheduled_date ? date('d/m/Y H:i', strtotime($request->scheduled_date)) : 'a confirmar',
            'status_label' => CentralVixMigModule::statuses()[$request->status] ?? $request->status,
        ];
    }

    protected static function replace($text, array $vars)
    {
        foreach ($vars as $k => $v) {
            $text = str_replace('{$' . $k . '}', $v, $text);
        }
        return $text;
    }

    protected static function prepareWhatsApp($event, $request, $client, array $vars)
    {
        if (CentralVixMigModule::dbSetting('whatsapp_enabled', '0') !== '1') {
            return;
        }
        $phone = preg_replace('/\D/', '', $client->phonenumber ?? '');
        if ($phone === '') {
            return;
        }

        $messages = [
            'request_received' => "CentralVix: recebemos sua solicitação de migração ({$vars['reference']}) para {$vars['domain']}.",
            'info_needed' => "CentralVix: precisamos de informações para a migração {$vars['reference']}. Acesse a área do cliente.",
            'scheduled' => "CentralVix: migração {$vars['reference']} agendada para {$vars['scheduled_date']}.",
            'started' => "CentralVix: migração de {$vars['domain']} iniciada. Acompanhe pelo painel.",
            'dns_change' => "CentralVix: fase de DNS da migração {$vars['reference']}. Verifique orientações no painel.",
            'completed' => "CentralVix: migração de {$vars['domain']} concluída. Obrigado!",
        ];
        if (!isset($messages[$event])) {
            return;
        }

        $payload = [
            'phone' => $phone,
            'message' => $messages[$event],
            'request_id' => $request->id,
            'event' => $event,
        ];
        CentralVixMigLogger::log($request->id, 'notify_whatsapp_prepared', $event, $payload);
    }

    public static function mapStatusToEvent($oldStatus, $newStatus)
    {
        if ($newStatus === 'aguardando_cliente') {
            return 'info_needed';
        }
        if ($newStatus === 'agendada' && $oldStatus !== 'agendada') {
            return 'scheduled';
        }
        if ($newStatus === 'backup' && !in_array($oldStatus, ['backup', 'migrando_arquivos', 'migrando_banco'], true)) {
            return 'started';
        }
        if ($newStatus === 'aguardando_dns') {
            return 'dns_change';
        }
        if ($newStatus === 'concluida') {
            return 'completed';
        }
        return null;
    }
}
