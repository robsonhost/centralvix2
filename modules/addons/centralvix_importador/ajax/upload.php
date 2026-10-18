<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

try {
    CentralVixImportadorHelper::requireAdmin();

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
    }

    $token = $_POST['token'] ?? '';
    if (!CentralVixImportadorHelper::validateCsrfToken($token)) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Token CSRF inválido. Recarregue a página.'], 403);
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede o limite do servidor.',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o limite do formulário.',
            UPLOAD_ERR_PARTIAL => 'Upload parcial do arquivo.',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado.',
        ];
        $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        $msg = isset($errorMessages[$code]) ? $errorMessages[$code] : 'Erro no upload do arquivo.';
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => $msg]);
    }

    $maxSizeMb = (int) CentralVixImportadorHelper::getSetting('max_file_size', 10);
    $maxSize = $maxSizeMb * 1048576;
    if ($_FILES['file']['size'] > $maxSize) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Arquivo excede o tamanho máximo de ' . $maxSizeMb . ' MB.',
        ]);
    }

    CentralVixImportadorHelper::ensureStorageDirs();

    $originalName = CentralVixImportadorHelper::sanitizeFilename($_FILES['file']['name']);
    $tempFile = $_FILES['file']['tmp_name'];
    $extHint = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if ($extHint !== 'csv') {
        $excelMessage = CentralVixImportadorHelper::getExcelReadinessMessage();
        if ($excelMessage !== '') {
            CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => $excelMessage]);
        }
    }

    $ext = CentralVixImportadorHelper::detectRealExtension($tempFile, $originalName);
    if (!$ext) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Tipo de arquivo não permitido. Use CSV, XLSX ou XLS.',
        ]);
    }

    $storedName = 'import_' . $_SESSION['adminid'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $saveResult = CentralVixImportadorHelper::saveUploadedFile($tempFile, $storedName);

    if (empty($saveResult['success'])) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => $saveResult['message'] ?? 'Falha ao salvar arquivo temporário.',
        ]);
    }

    $destPath = $saveResult['path'];

    $reader = new CentralVixSpreadsheetReader($destPath, $ext);
    $reader->analyze();
    $headers = $reader->getHeaders();
    $totalRows = $reader->getTotalRows();

    if ($totalRows === 0) {
        @unlink($destPath);
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Nenhum registro de dados encontrado no arquivo.']);
    }

    if (!WHMCS\Database\Capsule::schema()->hasTable('mod_centralvix_importacoes')) {
        @unlink($destPath);
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Tabelas do módulo não encontradas. Desative e reative o módulo em Configurações → Addon Modules.',
        ]);
    }

    $customFields = CentralVixImportadorHelper::getCustomClientFields();
    $autoMapping = CentralVixClientMapper::detectMapping($headers, $customFields);

    $importId = CentralVixImportLogger::createImport((int) $_SESSION['adminid'], $originalName, $totalRows);
    CentralVixImportLogger::updateImportMeta($importId, [
        'file_path' => $destPath,
        'mapping' => json_encode($autoMapping, JSON_UNESCAPED_UNICODE),
        'status' => 'analyzed',
    ]);

    $_SESSION['cvix_current_import'] = $importId;

    CentralVixImportadorHelper::jsonResponse([
        'success' => true,
        'message' => 'Arquivo analisado com sucesso.',
        'import_id' => $importId,
        'filename' => $originalName,
        'headers' => $headers,
        'total_rows' => $totalRows,
        'auto_mapping' => $autoMapping,
        'spreadsheet_available' => $reader->isSpreadsheetAvailable(),
    ]);
} catch (Throwable $e) {
    if (isset($destPath) && $destPath && file_exists($destPath)) {
        @unlink($destPath);
    }
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
