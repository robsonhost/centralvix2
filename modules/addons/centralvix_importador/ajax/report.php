<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixImportadorHelper::requireAdmin();

$action = $_GET['report_action'] ?? $_POST['report_action'] ?? 'details';

if ($action === 'download_csv') {
    handleDownloadCsv();
} elseif ($action === 'details') {
    handleDetails();
} else {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Ação inválida.']);
}

function handleDetails()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
    }

    $token = $_POST['token'] ?? '';
    if (!CentralVixImportadorHelper::validateCsrfToken($token)) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Token CSRF inválido.'], 403);
    }

    $importId = (int) ($_POST['import_id'] ?? 0);
    $import = CentralVixImportLogger::getImport($importId);

    if (!$import) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não encontrada.']);
    }

    $page = max(1, (int) ($_POST['page'] ?? 1));
    $perPage = min(100, max(10, (int) ($_POST['per_page'] ?? 50)));
    $statusFilter = isset($_POST['status']) ? preg_replace('/[^a-z]/', '', $_POST['status']) : '';

    $logs = CentralVixImportLogger::getImportLogs($importId, $page, $perPage, $statusFilter);

    CentralVixImportadorHelper::jsonResponse([
        'success' => true,
        'import' => [
            'id' => $import->id,
            'filename' => $import->filename,
            'total' => (int) $import->total,
            'imported' => (int) $import->imported,
            'updated' => (int) $import->updated,
            'skipped' => (int) $import->skipped,
            'errors' => (int) $import->errors,
            'status' => $import->status,
            'created_at' => $import->created_at,
            'finished_at' => $import->finished_at,
        ],
        'logs' => $logs,
    ]);
}

function handleDownloadCsv()
{
    $importId = (int) ($_GET['import_id'] ?? 0);
    $token = $_GET['token'] ?? '';

    if (!CentralVixImportadorHelper::validateCsrfToken($token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }

    $import = CentralVixImportLogger::getImport($importId);
    if (!$import) {
        http_response_code(404);
        die('Importação não encontrada.');
    }

    $lines = CentralVixImportLogger::exportCsv($importId);
    $filename = 'relatorio_importacao_' . $importId . '_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    foreach ($lines as $line) {
        fputcsv($output, $line, ';');
    }
    fclose($output);
    exit;
}
