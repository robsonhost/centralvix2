<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusNotificationService
{
    private static function canNotify($eventKey, $cooldownMinutes = 30)
    {
        $key = 'notify_last_' . md5($eventKey);
        $last = CentralVixStatusModule::dbSetting($key, '0');
        if ($last && (time() - (int) $last) < ($cooldownMinutes * 60)) {
            return false;
        }
        CentralVixStatusModule::setDbSetting($key, (string) time());
        return true;
    }

    public static function notifyIncidentCreated($incident)
    {
        if (!$incident || !self::canNotify('incident_' . $incident->id, 5)) {
            return;
        }
        self::send('Incidente criado: ' . $incident->title, self::incidentBody($incident));
    }

    public static function notifyServiceDown($service, $result)
    {
        if (!self::canNotify('down_' . $service->id, (int) CentralVixStatusModule::setting('notify_cooldown', 30))) {
            return;
        }
        self::send('Serviço offline: ' . $service->name, $result['message'] ?? 'Indisponível');
    }

    public static function notifyServiceRestored($service, $result)
    {
        if (!self::canNotify('up_' . $service->id, (int) CentralVixStatusModule::setting('notify_cooldown', 30))) {
            return;
        }
        self::send('Serviço restaurado: ' . $service->name, 'Operacional novamente.');
    }

    public static function notifyMaintenanceScheduled($m)
    {
        if (!$m || !self::canNotify('maint_sched_' . $m->id, 60)) {
            return;
        }
        self::send('Manutenção programada: ' . $m->title, $m->description);
    }

    public static function notifyMaintenanceStarted($m)
    {
        if (!$m || !self::canNotify('maint_start_' . $m->id, 60)) {
            return;
        }
        self::send('Manutenção iniciada: ' . $m->title, $m->description);
    }

    public static function notifyMaintenanceCompleted($m)
    {
        if (!$m || !self::canNotify('maint_done_' . $m->id, 60)) {
            return;
        }
        self::send('Manutenção concluída: ' . $m->title, 'Serviços normalizados.');
    }

    private static function incidentBody($incident)
    {
        return CentralVixStatusSecurity::sanitizeText($incident->description, 500);
    }

    private static function send($subject, $body)
    {
        if (CentralVixStatusModule::setting('notify_email', '') === 'on') {
            self::sendEmail($subject, $body);
        }
        if (CentralVixStatusModule::setting('notify_whatsapp', '') === 'on') {
            self::sendWhatsApp($subject . ' — ' . $body);
        }
    }

    private static function sendEmail($subject, $body)
    {
        $to = CentralVixStatusModule::setting('notify_email_to', '');
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $fullSubject = '[CentralVix Status] ' . $subject;
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        @mail($to, $fullSubject, $body, $headers);
    }

    private static function sendWhatsApp($message)
    {
        $endpoint = CentralVixStatusModule::setting('whatsapp_endpoint', '');
        $token = CentralVixStatusModule::setting('whatsapp_token', '');
        $phone = CentralVixStatusModule::setting('whatsapp_phone', '');
        if ($endpoint === '' || $token === '' || $phone === '') {
            return;
        }
        $payload = json_encode([
            'phone' => $phone,
            'message' => CentralVixStatusSecurity::sanitizeText($message, 1000),
            'instance' => CentralVixStatusModule::setting('whatsapp_instance', 'CentralVix'),
        ]);
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}
