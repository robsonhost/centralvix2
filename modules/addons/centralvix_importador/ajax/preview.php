<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixImportadorHelper::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
}

$token = $_POST['token'] ?? '';
if (!CentralVixImportadorHelper::validateCsrfToken($token)) {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
}

$importId = (int) ($_POST['import_id'] ?? 0);
$import = CentralVixImportLogger::getImport($importId);

if (!$import || (int) $import->admin_id !== (int) $_SESSION['adminid']) {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não encontrada ou acesso negado.']);
}

if (empty($import->file_path) || !file_exists($import->file_path)) {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Arquivo de importação não disponível.']);
}

try {
    $ext = pathinfo($import->filename, PATHINFO_EXTENSION);
    $reader = new CentralVixSpreadsheetReader($import->file_path, $ext);
    $reader->analyze();
    $preview = $reader->getPreviewRows(10);
    $headers = $reader->getHeaders();

    $previewData = [];
    foreach ($preview as $row) {
        $previewData[] = $row['data'];
    }

    CentralVixImportadorHelper::jsonResponse([
        'success' => true,
        'headers' => $headers,
        'preview' => $previewData,
        'total_rows' => $reader->getTotalRows(),
        'filename' => $import->filename,
    ]);
} catch (Exception $e) {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => $e->getMessage()]);
}
