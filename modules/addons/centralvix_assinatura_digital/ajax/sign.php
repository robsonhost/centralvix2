<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$clientId = (int) ($_SESSION['uid'] ?? 0);
$isToken = !empty($_POST['access_token']);

if ($isToken) {
    $valid = CentralVixSignSignatureService::validateToken($_POST['access_token'], (int) ($_POST['document_id'] ?? 0));
    if (!$valid['success']) {
        CentralVixSignModule::json($valid, 403);
    }
    $clientId = (int) $valid['client_id'];
} else {
    if (!$clientId) {
        CentralVixSignModule::json(['success' => false, 'message' => 'Login necessário.'], 403);
    }
    if (!CentralVixSignModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
        CentralVixSignModule::json(['success' => false, 'message' => 'CSRF inválido.'], 403);
    }
}

$documentId = (int) ($_POST['document_id'] ?? 0);
if ($isToken && $documentId !== (int) $valid['document_id']) {
    CentralVixSignModule::json(['success' => false, 'message' => 'IDOR bloqueado.'], 403);
}

CentralVixSignModule::json(CentralVixSignSignatureService::sign($documentId, $clientId, [
    'accept_terms' => !empty($_POST['accept_terms']),
    'signature_data' => $_POST['signature_data'] ?? '',
    'signer_document' => $_POST['signer_document'] ?? '',
]));
