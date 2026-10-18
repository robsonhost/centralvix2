<?php

define('CLIENTAREA', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixSignModule::autoload();

$token = $_GET['t'] ?? '';
$valid = CentralVixSignSignatureService::validateToken($token);
if (!$valid['success']) {
    http_response_code(403);
    exit('Acesso negado.');
}

CentralVixSignDocumentService::serveFile((int) $valid['document_id'], 'original', (int) $valid['client_id'], $token);
