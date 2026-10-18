<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

$isToken = !empty($_POST['access_token']);
$clientId = (int) ($_SESSION['uid'] ?? 0);
$documentId = (int) ($_POST['document_id'] ?? 0);

if ($isToken) {
    $valid = CentralVixSignSignatureService::validateToken($_POST['access_token'], $documentId);
    if (!$valid['success']) {
        CentralVixSignModule::json($valid, 403);
    }
    $clientId = (int) $valid['client_id'];
    if ($documentId !== (int) $valid['document_id']) {
        CentralVixSignModule::json(['success' => false, 'message' => 'IDOR bloqueado.'], 403);
    }
} else {
    if (!$clientId) {
        CentralVixSignModule::json(['success' => false, 'message' => 'Login necessário.'], 403);
    }
    if (!CentralVixSignModule::validateCsrf($_POST['csrf_token'] ?? '', 'client')) {
        CentralVixSignModule::json(['success' => false, 'message' => 'CSRF inválido.'], 403);
    }
}

CentralVixSignModule::json(CentralVixSignSignatureService::reject($documentId, $clientId, $_POST['reason'] ?? ''));
