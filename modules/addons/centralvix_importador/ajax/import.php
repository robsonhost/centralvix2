<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

CentralVixImportadorHelper::requireAdmin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'save_mapping') {
    handleSaveMapping();
} elseif ($action === 'start') {
    handleStartImport();
} elseif ($action === 'batch') {
    handleBatchImport();
} else {
    CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Ação inválida.']);
}

function handleSaveMapping()
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

    if (!$import || (int) $import->admin_id !== (int) $_SESSION['adminid']) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não encontrada.']);
    }

    $mapping = CentralVixImportadorHelper::parseMappingFromRequest($import);
    if ($mapping === null || empty($mapping)) {
        CentralVixImportadorHelper::jsonResponse([
            'success' => false,
            'message' => 'Mapeamento inválido ou vazio. Volte à etapa 3 e mapeie as colunas (incluindo E-mail).',
        ]);
    }

    if (!CentralVixImportadorHelper::mappingHasEmail($mapping)) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'É obrigatório mapear uma coluna para o campo E-mail (email).']);
    }

    $importOptions = [
        'skip_existing_email' => !empty($_POST['skip_existing_email']),
        'update_existing' => !empty($_POST['update_existing']),
        'validate_email' => !empty($_POST['validate_email']),
        'trim_spaces' => !empty($_POST['trim_spaces']),
        'remove_invalid_chars' => !empty($_POST['remove_invalid_chars']),
        'normalize_phone' => !empty($_POST['normalize_phone']),
        'normalize_state' => !empty($_POST['normalize_state']),
        'default_country' => substr(strip_tags($_POST['default_country'] ?? 'BR'), 0, 5),
        'default_currency' => substr(strip_tags($_POST['default_currency'] ?? ''), 0, 10),
        'default_groupid' => (int) ($_POST['default_groupid'] ?? 0),
        'default_language' => substr(strip_tags($_POST['default_language'] ?? 'portuguese-br'), 0, 30),
        'generate_password' => !empty($_POST['generate_password']),
        'check_tax_id' => !empty($_POST['check_tax_id']),
        'tax_id_custom_field' => (int) ($_POST['tax_id_custom_field'] ?? 0) ?: CentralVixImportadorHelper::detectTaxCustomFieldId(),
        'fill_required_custom_fields' => !isset($_POST['fill_required_custom_fields']) || !empty($_POST['fill_required_custom_fields']),
        'default_custom_field_text' => CentralVixImportadorHelper::sanitizeImportText($_POST['default_custom_field_text'] ?? 'Importação em massa', 120),
        'default_phone' => CentralVixImportadorHelper::sanitizeImportText($_POST['default_phone'] ?? '(11) 99999-9999', 30),
        'default_address1' => CentralVixImportadorHelper::sanitizeImportText($_POST['default_address1'] ?? 'Não informado', 120),
        'default_city' => CentralVixImportadorHelper::sanitizeImportText($_POST['default_city'] ?? 'Não informado', 80),
        'default_state' => strtoupper(CentralVixImportadorHelper::sanitizeImportText($_POST['default_state'] ?? 'SP', 5)),
        'sync_tax_id_custom_field' => !isset($_POST['sync_tax_id_custom_field']) || !empty($_POST['sync_tax_id_custom_field']),
        'allow_missing_tax_id' => !array_key_exists('allow_missing_tax_id', $_POST) || !empty($_POST['allow_missing_tax_id']),
        'generate_missing_tax_id' => !array_key_exists('generate_missing_tax_id', $_POST) || !empty($_POST['generate_missing_tax_id']),
    ];

    CentralVixImportLogger::updateImportMeta($importId, [
        'mapping' => json_encode($mapping, JSON_UNESCAPED_UNICODE),
        'settings' => json_encode($importOptions, JSON_UNESCAPED_UNICODE),
        'status' => 'ready',
    ]);

    CentralVixImportadorHelper::jsonResponse([
        'success' => true,
        'message' => 'Mapeamento e configurações salvos.',
        'import_id' => $importId,
        'total_rows' => (int) $import->total,
    ]);
}

function handleStartImport()
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

    if (!$import || (int) $import->admin_id !== (int) $_SESSION['adminid']) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não encontrada.']);
    }

    if (empty($import->mapping) || empty($import->settings)) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Configure o mapeamento antes de iniciar.']);
    }

    WHMCS\Database\Capsule::table('mod_centralvix_importacao_logs')
        ->where('import_id', $importId)
        ->delete();

    CentralVixImportLogger::updateImportMeta($importId, [
        'status' => 'processing',
        'current_offset' => 0,
        'imported' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ]);

    CentralVixImportadorHelper::jsonResponse([
        'success' => true,
        'message' => 'Importação iniciada.',
        'import_id' => $importId,
        'total_rows' => (int) $import->total,
    ]);
}

function handleBatchImport()
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

    if (!$import || (int) $import->admin_id !== (int) $_SESSION['adminid']) {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não encontrada.']);
    }

    if ($import->status !== 'processing') {
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Importação não está em processamento.']);
    }

    $batchSize = (int) CentralVixImportadorHelper::getSetting('batch_size', 25);
    if (!in_array($batchSize, [10, 25, 50], true)) {
        $batchSize = CentralVixImportadorHelper::BATCH_SIZE;
    }

    try {
        $importer = new CentralVixImporter($importId);
        $result = $importer->processBatch($batchSize);

        CentralVixImportadorHelper::jsonResponse([
            'success' => true,
            'import_id' => $importId,
            'offset' => $result['offset'],
            'total' => $result['total'],
            'completed' => $result['completed'],
            'progress_percent' => $result['progress_percent'],
            'counters' => $result['counters'],
            'batch_counters' => $result['batch_counters'],
            'recent_results' => array_slice($result['results'], -5),
        ]);
    } catch (Exception $e) {
        CentralVixImportLogger::finishImport($importId, 'failed');
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}
