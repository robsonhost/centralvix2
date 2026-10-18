<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixSignLogger
{
    public static function log($documentId, $eventType, $message, array $context = [])
    {
        $dir = CentralVixSignModule::storagePath() . '/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] DOC:%d [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $documentId,
            $eventType,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        @file_put_contents($dir . '/assinatura.log', $line, FILE_APPEND | LOCK_EX);
    }
}
