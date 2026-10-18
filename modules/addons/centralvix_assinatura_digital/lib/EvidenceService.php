<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSignEvidenceService
{
    public static function record($documentId, $clientId, $eventType, $message, array $extra = [])
    {
        $evidence = array_merge([
            'ip' => CentralVixSignSecurity::clientIp(),
            'user_agent' => CentralVixSignSecurity::userAgent(),
            'timestamp' => date('c'),
        ], $extra);

        Capsule::table('mod_centralvix_sign_events')->insert([
            'document_id' => (int) $documentId,
            'client_id' => (int) $clientId,
            'event_type' => $eventType,
            'message' => CentralVixSignSecurity::sanitizeText($message, 2000),
            'ip_address' => CentralVixSignSecurity::clientIp(),
            'user_agent' => CentralVixSignSecurity::userAgent(),
            'evidence_json' => json_encode($evidence, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixSignLogger::log($documentId, $eventType, $message, $evidence);
    }

    public static function timeline($documentId)
    {
        return Capsule::table('mod_centralvix_sign_events')
            ->where('document_id', (int) $documentId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public static function auditList($limit = 100)
    {
        return Capsule::table('mod_centralvix_sign_events as e')
            ->join('mod_centralvix_sign_documents as d', 'd.id', '=', 'e.document_id')
            ->select('e.*', 'd.title as document_title', 'd.transaction_id')
            ->orderBy('e.id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function formatEvent($event)
    {
        return [
            'type' => $event->event_type,
            'message' => $event->message,
            'ip' => $event->ip_address,
            'user_agent' => $event->user_agent,
            'date' => date('d/m/Y H:i:s', strtotime($event->created_at)),
            'evidence' => json_decode($event->evidence_json ?? '{}', true),
        ];
    }
}
