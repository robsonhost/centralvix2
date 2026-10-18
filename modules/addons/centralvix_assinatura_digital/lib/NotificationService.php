<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixSignNotificationService
{
    public static function signatureRequested($doc)
    {
        if (CentralVixSignModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $client = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $doc->client_id)->first();
        if (!$client || empty($client->email)) {
            return;
        }
        $link = CentralVixSignModule::systemUrl() . '/index.php?m=centralvix_assinatura_digital&action=sign&id=' . (int) $doc->id;
        $body = '<p>Voce possui um documento aguardando assinatura:</p><p><strong>' . CentralVixSignSecurity::e($doc->title) . '</strong></p>'
            . '<p><a href="' . CentralVixSignSecurity::e($link) . '">Acessar documento</a></p>'
            . '<p style="font-size:12px;color:#64748b;">Este processo registra evidencias tecnicas de aceite. Consulte os termos do servico.</p>';
        self::send($client->email, (int) $doc->client_id, '[CentralVix] Documento para assinatura — ' . $doc->title, $body);
    }

    public static function documentSigned($doc, $client)
    {
        if (CentralVixSignModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $body = '<p>O documento <strong>' . CentralVixSignSecurity::e($doc->title) . '</strong> foi assinado.</p>'
            . '<p>ID: ' . CentralVixSignSecurity::e($doc->transaction_id) . '</p>';
        self::send($client->email ?? '', (int) $doc->client_id, '[CentralVix] Documento assinado — ' . $doc->title, $body);
    }

    protected static function send($email, $clientId, $subject, $html)
    {
        try {
            if (function_exists('sendMessage')) {
                sendMessage('CentralVix Sign', $clientId, ['customsubject' => $subject, 'custommessage' => $html]);
            } elseif ($email) {
                @mail($email, $subject, $html, "Content-type: text/html; charset=UTF-8\r\n");
            }
        } catch (Throwable $e) {
            CentralVixSignLogger::log($clientId, 'email_error', $e->getMessage());
        }
    }
}
