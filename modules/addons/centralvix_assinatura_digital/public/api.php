<?php

define('CLIENTAREA', true);

$whmcsPath = dirname(__DIR__, 3);
if (!is_file($whmcsPath . '/init.php')) {
    $whmcsPath = dirname(__DIR__, 4);
}
require_once $whmcsPath . '/init.php';

require_once dirname(__DIR__) . '/lib/Security.php';
CentralVixSignModule::autoload();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? $_POST['action'] ?? '');
$token = $_POST['access_token'] ?? '';
$documentId = (int) ($_POST['document_id'] ?? 0);

$valid = CentralVixSignSignatureService::validateToken($token, $documentId);
if (!$valid['success']) {
    http_response_code(403);
    echo json_encode($valid, JSON_UNESCAPED_UNICODE);
    exit;
}

$clientId = (int) $valid['client_id'];
if ($documentId !== (int) $valid['document_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'IDOR bloqueado.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'reject') {
    $result = CentralVixSignSignatureService::reject($documentId, $clientId, $_POST['reason'] ?? '');
} else {
    $result = CentralVixSignSignatureService::sign($documentId, $clientId, [
        'accept_terms' => !empty($_POST['accept_terms']),
        'signature_data' => $_POST['signature_data'] ?? '',
        'signer_document' => $_POST['signer_document'] ?? '',
    ]);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
exit;
