<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/ModuleHelper.php';
CentralVixImportadorHelper::autoloadLib();

function centralvix_importador_config()
{
    return [
        'name' => 'CentralVix Importador de Clientes',
        'description' => 'Importe clientes em massa a partir de arquivos CSV e Excel (.xlsx/.xls) com mapeamento de campos, processamento em lotes e relatório completo.',
        'version' => '1.0.0',
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_file_size' => [
                'FriendlyName' => 'Tamanho máximo do arquivo (MB)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '10',
                'Description' => 'Tamanho máximo permitido para upload de arquivos.',
            ],
            'batch_size' => [
                'FriendlyName' => 'Clientes por lote',
                'Type' => 'dropdown',
                'Options' => '10,25,50',
                'Default' => '25',
                'Description' => 'Quantidade de registros processados por requisição AJAX.',
            ],
            'default_currency' => [
                'FriendlyName' => 'Moeda padrão',
                'Type' => 'text',
                'Size' => '10',
                'Default' => 'BRL',
                'Description' => 'Moeda padrão quando não informada na planilha.',
            ],
            'default_country' => [
                'FriendlyName' => 'País padrão',
                'Type' => 'text',
                'Size' => '5',
                'Default' => 'BR',
                'Description' => 'País padrão quando vazio na planilha.',
            ],
            'default_language' => [
                'FriendlyName' => 'Idioma padrão',
                'Type' => 'text',
                'Size' => '20',
                'Default' => 'portuguese-br',
                'Description' => 'Idioma padrão para novos clientes.',
            ],
            'default_groupid' => [
                'FriendlyName' => 'Grupo de clientes padrão',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '',
                'Description' => 'ID do grupo de clientes padrão (deixe vazio para nenhum).',
            ],
            'auto_cleanup_hours' => [
                'FriendlyName' => 'Limpeza automática (horas)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '24',
                'Description' => 'Remove arquivos temporários mais antigos que X horas.',
            ],
        ],
    ];
}

function centralvix_importador_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_importacoes')) {
            Capsule::schema()->create('mod_centralvix_importacoes', function ($table) {
                $table->increments('id');
                $table->integer('admin_id')->unsigned();
                $table->string('filename', 255);
                $table->integer('total')->unsigned()->default(0);
                $table->integer('imported')->unsigned()->default(0);
                $table->integer('updated')->unsigned()->default(0);
                $table->integer('skipped')->unsigned()->default(0);
                $table->integer('errors')->unsigned()->default(0);
                $table->integer('current_offset')->unsigned()->default(0);
                $table->text('settings')->nullable();
                $table->text('mapping')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('status', 50)->default('pending');
                $table->dateTime('created_at');
                $table->dateTime('finished_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_importacao_logs')) {
            Capsule::schema()->create('mod_centralvix_importacao_logs', function ($table) {
                $table->increments('id');
                $table->integer('import_id')->unsigned();
                $table->integer('row_number')->unsigned();
                $table->string('name', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->integer('client_id')->unsigned()->default(0);
                $table->string('status', 50);
                $table->text('message')->nullable();
                $table->dateTime('created_at');
                $table->index('import_id');
                $table->index('status');
            });
        }

        CentralVixImportadorHelper::ensureStorageDirs();

        return [
            'status' => 'success',
            'description' => 'Módulo CentralVix Importador ativado com sucesso. Tabelas criadas e pasta de armazenamento configurada.',
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'description' => 'Erro ao ativar módulo: ' . $e->getMessage(),
        ];
    }
}

function centralvix_importador_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desativado. Os históricos de importação foram preservados no banco de dados.',
    ];
}

function centralvix_importador_output($vars)
{
    CentralVixImportadorHelper::ensureStorageDirs();
    CentralVixImporter::cleanupTempFiles((int) CentralVixImportadorHelper::getSetting('auto_cleanup_hours', 24));

    if (!empty($_GET['cvix_ajax'])) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $endpoint = preg_replace('/[^a-z_]/', '', $_GET['cvix_ajax']);
        $ajaxFile = __DIR__ . '/ajax/' . $endpoint . '.php';
        if (file_exists($ajaxFile)) {
            try {
                require $ajaxFile;
            } catch (Throwable $e) {
                CentralVixImportadorHelper::jsonResponse([
                    'success' => false,
                    'message' => 'Erro interno: ' . $e->getMessage(),
                ], 500);
            }
            exit;
        }
        CentralVixImportadorHelper::jsonResponse(['success' => false, 'message' => 'Endpoint AJAX inválido.'], 404);
    }

    $tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', $_GET['tab']) : 'dashboard';
    $allowedTabs = ['dashboard', 'import', 'history', 'settings', 'report'];
    if (!in_array($tab, $allowedTabs, true)) {
        $tab = 'dashboard';
    }

    $modulelink = $vars['modulelink'];
    $csrfToken = CentralVixImportadorHelper::generateCsrfToken();
    $settings = CentralVixImportadorHelper::getModuleSettings();
    $batchSize = (int) CentralVixImportadorHelper::getSetting('batch_size', 25);
    if (!in_array($batchSize, [10, 25, 50], true)) {
        $batchSize = 25;
    }

    $maxFileSizeMb = (int) CentralVixImportadorHelper::getSetting('max_file_size', 10);
    $maxFileSizeBytes = $maxFileSizeMb * 1048576;

    $assetBase = '../modules/addons/centralvix_importador/assets';
    $assetVer = '1.0.14';
    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink($assetVer);
    echo '<script>var CVIX_IMPORTER = ' . json_encode([
        'moduleLink' => $modulelink,
        'csrfToken' => $csrfToken,
        'batchSize' => $batchSize,
        'maxFileSize' => $maxFileSizeBytes,
        'ajaxUpload' => CentralVixImportadorHelper::getAdminAjaxUrl('upload'),
        'ajaxPreview' => CentralVixImportadorHelper::getAdminAjaxUrl('preview'),
        'ajaxImport' => CentralVixImportadorHelper::getAdminAjaxUrl('import'),
        'ajaxReport' => CentralVixImportadorHelper::getAdminAjaxUrl('report'),
        'ajaxPing' => CentralVixImportadorHelper::getAdminAjaxUrl('ping'),
    ], JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<script src="' . $assetBase . '/js/importer.js?v=' . $assetVer . '"></script>';

    CentralVixImportadorHelper::renderTemplate('layout_header', [
        'modulelink' => $modulelink,
        'tab' => $tab,
    ]);

    switch ($tab) {
        case 'import':
            CentralVixImportadorHelper::renderTemplate('import', [
                'modulelink' => $modulelink,
                'csrfToken' => $csrfToken,
                'customFields' => CentralVixImportadorHelper::getCustomClientFields(),
                'whmcsFields' => CentralVixClientMapper::getWhmcsFields(),
                'currencies' => CentralVixImportadorHelper::getCurrencies(),
                'clientGroups' => CentralVixImportadorHelper::getClientGroups(),
                'countries' => CentralVixImportadorHelper::getCountries(),
                'languages' => CentralVixImportadorHelper::getLanguages(),
                'settings' => $settings,
                'detectedTaxCustomFieldId' => CentralVixImportadorHelper::detectTaxCustomFieldId(),
                'storageWritable' => CentralVixImportadorHelper::isStorageWritable(),
                'storagePath' => CentralVixImportadorHelper::storagePath(),
            ]);
            break;

        case 'history':
            $page = max(1, (int) ($_GET['page'] ?? 1));
            $history = CentralVixImportLogger::getHistory($page, 20);
            CentralVixImportadorHelper::renderTemplate('history', [
                'modulelink' => $modulelink,
                'history' => $history,
            ]);
            break;

        case 'settings':
            CentralVixImportadorHelper::renderTemplate('settings', [
                'modulelink' => $modulelink,
                'settings' => $settings,
                'spreadsheetAvailable' => CentralVixImportadorHelper::isSpreadsheetAvailable(),
            ]);
            break;

        case 'report':
            $importId = (int) ($_GET['import_id'] ?? 0);
            $import = CentralVixImportLogger::getImport($importId);
            if (!$import) {
                echo '<div class="cvix-alert cvix-alert-danger">Importação não encontrada.</div>';
                break;
            }
            $logPage = max(1, (int) ($_GET['log_page'] ?? 1));
            $statusFilter = isset($_GET['status']) ? preg_replace('/[^a-z]/', '', $_GET['status']) : '';
            $logs = CentralVixImportLogger::getImportLogs($importId, $logPage, 50, $statusFilter);
            CentralVixImportadorHelper::renderTemplate('report', [
                'modulelink' => $modulelink,
                'import' => $import,
                'logs' => $logs,
                'adminName' => CentralVixImportadorHelper::getAdminName($import->admin_id),
                'statusFilter' => $statusFilter,
            ]);
            break;

        case 'dashboard':
        default:
            CentralVixImportadorHelper::renderTemplate('dashboard', [
                'modulelink' => $modulelink,
                'stats' => CentralVixImportadorHelper::getDashboardStats(),
                'recentImports' => CentralVixImportLogger::getHistory(1, 5),
            ]);
            break;
    }

    CentralVixImportadorHelper::renderTemplate('layout_footer');
}
