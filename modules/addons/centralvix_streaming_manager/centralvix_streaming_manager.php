<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixStreamingModule::autoload();

function centralvix_streaming_manager_config()
{
    return [
        'name' => 'CentralVix Streaming Manager',
        'description' => 'Gerenciamento de streaming de áudio e vídeo integrado ao WHMCS.',
        'version' => CentralVixStreamingModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'api_timeout' => [
                'FriendlyName' => 'Timeout API (segundos)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '15',
            ],
            'stats_retention_days' => [
                'FriendlyName' => 'Retenção estatísticas (dias)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
            ],
            'stats_interval' => [
                'FriendlyName' => 'Coleta stats (minutos)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '15',
            ],
            'notify_client' => [
                'FriendlyName' => 'Notificar cliente por e-mail',
                'Type' => 'yesno',
            ],
            'encryption_seed' => [
                'FriendlyName' => 'Seed criptografia (opcional)',
                'Type' => 'password',
                'Size' => '50',
                'Description' => 'Vazio = usa cc_encryption_hash do WHMCS',
            ],
            'product_ids' => [
                'FriendlyName' => 'IDs produtos streaming (csv)',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'Ex: 1,5,12 — hooks de provisionamento',
            ],
        ],
    ];
}

function centralvix_streaming_manager_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_stream_servers')) {
            Capsule::schema()->create('mod_centralvix_stream_servers', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('type', 30)->default('icecast');
                $table->string('hostname', 255);
                $table->integer('api_port')->unsigned()->default(8000);
                $table->integer('stream_port')->unsigned()->default(8000);
                $table->boolean('use_ssl')->default(0);
                $table->string('api_user', 100)->nullable();
                $table->text('api_password_enc')->nullable();
                $table->integer('capacity')->unsigned()->default(100);
                $table->boolean('active')->default(1);
                $table->string('location', 100)->nullable();
                $table->string('provisioning_url', 500)->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_stream_accounts')) {
            Capsule::schema()->create('mod_centralvix_stream_accounts', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned()->default(0);
                $table->integer('server_id')->unsigned();
                $table->string('stream_username', 100);
                $table->text('stream_password_enc')->nullable();
                $table->integer('port')->unsigned()->default(8000);
                $table->string('mountpoint', 100)->default('/stream');
                $table->integer('max_listeners')->unsigned()->default(100);
                $table->integer('bitrate')->unsigned()->default(128);
                $table->integer('disk_space_mb')->unsigned()->default(0);
                $table->boolean('autodj')->default(0);
                $table->string('status', 30)->default('pending');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index(['client_id', 'status']);
                $table->index('service_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_stream_stats')) {
            Capsule::schema()->create('mod_centralvix_stream_stats', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned();
                $table->integer('listeners')->unsigned()->default(0);
                $table->integer('peak_listeners')->unsigned()->default(0);
                $table->integer('bitrate')->unsigned()->default(0);
                $table->bigInteger('bytes_transfer')->unsigned()->default(0);
                $table->boolean('online')->default(0);
                $table->dateTime('recorded_at');
                $table->index(['account_id', 'recorded_at']);
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_stream_logs')) {
            Capsule::schema()->create('mod_centralvix_stream_logs', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned()->default(0);
                $table->integer('admin_id')->unsigned()->default(0);
                $table->integer('client_id')->unsigned()->default(0);
                $table->string('action', 100);
                $table->string('result', 50);
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('created_at');
                $table->index('created_at');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_stream_settings')) {
            Capsule::schema()->create('mod_centralvix_stream_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        return ['status' => 'success', 'description' => 'CentralVix Streaming Manager ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Erro: ' . $e->getMessage()];
    }
}

function centralvix_streaming_manager_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desativado. Dados preservados.',
    ];
}

function centralvix_streaming_manager_output($vars)
{
    if (!empty($_GET['cvix_stream_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        CentralVixStreamingModule::requireAdmin();
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_stream_ajax']);
        $file = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($file)) {
            require $file;
            exit;
        }
        CentralVixStreamingModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cvix_save_account'])) {
        CentralVixStreamingModule::requireAdmin();
        if (CentralVixStreamingModule::validateCsrf($_POST['token'] ?? '')) {
            $result = CentralVixStreamingService::saveAccount($_POST, (int) ($_POST['id'] ?? 0));
            header('Location: ' . $vars['modulelink'] . '&tab=accounts&edit=' . ($result['id'] ?? 0));
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cvix_save_plans'])) {
        CentralVixStreamingModule::requireAdmin();
        if (CentralVixStreamingModule::validateCsrf($_POST['token'] ?? '')) {
            $plans = json_decode($_POST['plans_json'] ?? '[]', true);
            if (is_array($plans)) {
                CentralVixStreamingService::savePlans($plans);
            }
            header('Location: ' . $vars['modulelink'] . '&tab=plans&saved=1');
            exit;
        }
    }

    $tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', $_GET['tab']) : 'dashboard';
    $allowed = ['dashboard', 'servers', 'accounts', 'plans', 'statistics', 'settings', 'logs'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $modulelink = $vars['modulelink'];
    $csrfToken = CentralVixStreamingModule::csrfToken('admin');
    $assetBase = '../modules/addons/centralvix_streaming_manager/assets';
    $ver = CentralVixStreamingModule::VERSION;

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink($ver);
    echo '<script>var CVIX_STREAM=' . json_encode([
        'moduleLink' => $modulelink,
        'csrf' => $csrfToken,
        'ajaxServerTest' => CentralVixStreamingModule::ajaxUrl('server_test'),
        'ajaxSaveServer' => CentralVixStreamingModule::ajaxUrl('save_server'),
        'ajaxAccountAction' => CentralVixStreamingModule::ajaxUrl('account_action'),
        'ajaxStats' => CentralVixStreamingModule::ajaxUrl('stats'),
    ], JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<script src="' . CentralVixStreamingSecurity::e($assetBase) . '/js/admin.js?v=' . $ver . '"></script>';

    CentralVixStreamingModule::renderAdmin($tab, [
        'modulelink' => $modulelink,
        'tab' => $tab,
        'csrfToken' => $csrfToken,
        'stats' => CentralVixStreamingService::dashboardStats(),
        'servers' => CentralVixStreamingService::listServers(),
        'accounts' => CentralVixStreamingService::listAccounts(),
        'serverTypes' => CentralVixStreamingModule::serverTypes(),
        'accountStatuses' => CentralVixStreamingModule::accountStatuses(),
        'products' => CentralVixStreamingService::listProducts(),
        'plans' => CentralVixStreamingService::getPlans(),
        'logs' => CentralVixStreamingLogger::listLogs(100),
        'settings' => CentralVixStreamingModule::moduleSettings(),
    ]);
}

function centralvix_streaming_manager_clientarea($vars)
{
    CentralVixStreamingModule::autoload();

    if (!empty($_GET['cvix_stream_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        CentralVixStreamingModule::requireClient();
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_stream_ajax']);
        $file = dirname(__FILE__) . '/ajax/' . $action . '.php';
        if (is_file($file)) {
            require $file;
            exit;
        }
        CentralVixStreamingModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    $clientId = (int) ($_SESSION['uid'] ?? 0);
    $page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');
    $allowed = ['dashboard', 'player', 'statistics'];
    if (!in_array($page, $allowed, true)) {
        $page = 'dashboard';
    }

    $accountId = (int) ($_GET['account'] ?? 0);
    $accounts = CentralVixStreamingService::listAccounts($clientId);
    if ($accountId) {
        $current = CentralVixStreamingService::getAccount($accountId, $clientId);
    } else {
        $current = $accounts->first();
    }

    $csrfToken = CentralVixStreamingModule::csrfToken('client');
    $assetBase = 'modules/addons/centralvix_streaming_manager/assets';
    $ver = CentralVixStreamingModule::VERSION;

    ob_start();
    CentralVixStreamingModule::renderClient($page, [
        'accounts' => $accounts,
        'account' => $current,
        'clientId' => $clientId,
        'csrfToken' => $csrfToken,
        'moduleLink' => 'index.php?m=centralvix_streaming_manager',
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixStreamingModule::lang('client_title', 'Streaming'),
        'breadcrumb' => ['index.php?m=centralvix_streaming_manager' => 'Streaming'],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => [
            'htmlContent' => $html,
            'assetBase' => $assetBase,
            'ver' => $ver,
            'csrf' => $csrfToken,
            'ajaxAccountAction' => CentralVixStreamingModule::ajaxUrl('account_action', true),
            'ajaxStats' => CentralVixStreamingModule::ajaxUrl('stats', true),
        ],
    ];
}
