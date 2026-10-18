<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixStreamingNotificationService
{
    public static function notifyClient($clientId, $subject, $body)
    {
        if (CentralVixStreamingModule::setting('notify_client', '') !== 'on') {
            return;
        }
        if (!function_exists('localAPI')) {
            return;
        }
        try {
            localAPI('SendEmail', [
                'id' => (int) $clientId,
                'customtype' => 'general',
                'customsubject' => '[CentralVix Streaming] ' . $subject,
                'custommessage' => nl2br(CentralVixStreamingSecurity::e($body)),
            ]);
        } catch (Throwable $e) {
            // silencioso
        }
    }
}
