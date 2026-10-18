<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixLicNotificationService
{
    public static function licenseCreated($licenseId, $clientId, $key, $productName)
    {
        if (CentralVixLicModule::dbSetting('email_enabled', '1') !== '1') {
            return;
        }
        $client = WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $clientId)->first();
        if (!$client || empty($client->email)) {
            return;
        }
        $link = CentralVixLicModule::systemUrl() . '/index.php?m=centralvix_licenciamento&action=license&id=' . (int) $licenseId;
        $html = '<p>Sua licença <strong>' . CentralVixLicSecurity::e($productName) . '</strong> foi criada.</p>'
            . '<p>Chave: <code>' . CentralVixLicSecurity::e($key) . '</code></p>'
            . '<p><a href="' . CentralVixLicSecurity::e($link) . '">Ver licença</a></p>';
        try {
            if (function_exists('sendMessage')) {
                sendMessage('CentralVix Licenciamento', (int) $clientId, [
                    'customsubject' => '[CentralVix] Licença criada — ' . $productName,
                    'custommessage' => $html,
                ]);
            } else {
                @mail($client->email, '[CentralVix] Licença criada', $html, "Content-type: text/html; charset=UTF-8\r\n");
            }
        } catch (Throwable $e) {
            CentralVixLicLogger::log($licenseId, 'email_error', $e->getMessage(), [], $clientId);
        }
    }
}
