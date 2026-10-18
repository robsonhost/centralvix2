<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixAutoDJModule::autoload();
CentralVixAutoDJStorageService::ensureStorageRoot();

function centralvix_autodj_manager_config()
{
    return [
        'name' => 'CentralVix AutoDJ Manager',
        'description' => 'Gerenciamento de AutoDJ, biblioteca musical, playlists e programação para streaming de áudio.',
        'version' => CentralVixAutoDJModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_upload_mb' => [
                'FriendlyName' => 'Upload máximo (MB)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '50',
            ],
            'allow_aac' => [
                'FriendlyName' => 'Permitir AAC/M4A',
                'Type' => 'yesno',
            ],
            'default_quota_mb' => [
                'FriendlyName' => 'Quota padrão (MB)',
                'Type' => 'text',
                'Default' => '5120',
                'Description' => 'Ex: 5120 = 5 GB',
            ],
            'api_timeout' => [
                'FriendlyName' => 'Timeout API (seg)',
                'Type' => 'text',
                'Default' => '20',
            ],
        ],
    ];
}

function centralvix_autodj_manager_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_accounts')) {
            Capsule::schema()->create('mod_centralvix_autodj_accounts', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned()->default(0);
                $table->string('name', 150)->default('Minha Rádio');
                $table->string('provider_type', 30)->default('none');
                $table->text('provider_config')->nullable();
                $table->integer('quota_mb')->unsigned()->default(5120);
                $table->string('status', 30)->default('active');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_media')) {
            Capsule::schema()->create('mod_centralvix_autodj_media', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('media_type', 30)->default('music');
                $table->string('stored_name', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 100)->nullable();
                $table->bigInteger('size_bytes')->unsigned()->default(0);
                $table->integer('duration_seconds')->unsigned()->default(0);
                $table->dateTime('created_at');
                $table->index(['account_id', 'client_id']);
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_playlists')) {
            Capsule::schema()->create('mod_centralvix_autodj_playlists', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('name', 150);
                $table->string('description', 500)->nullable();
                $table->string('playlist_type', 30)->default('general');
                $table->boolean('random_mode')->default(0);
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_playlist_items')) {
            Capsule::schema()->create('mod_centralvix_autodj_playlist_items', function ($table) {
                $table->increments('id');
                $table->integer('playlist_id')->unsigned();
                $table->integer('media_id')->unsigned();
                $table->integer('sort_order')->default(0);
                $table->index('playlist_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_schedules')) {
            Capsule::schema()->create('mod_centralvix_autodj_schedules', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('title', 150);
                $table->date('schedule_date')->nullable();
                $table->text('days_of_week')->nullable();
                $table->time('time_start');
                $table->time('time_end');
                $table->integer('playlist_id')->unsigned()->default(0);
                $table->integer('priority')->default(0);
                $table->string('schedule_type', 30)->default('program');
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_settings')) {
            Capsule::schema()->create('mod_centralvix_autodj_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_autodj_logs')) {
            Capsule::schema()->create('mod_centralvix_autodj_logs', function ($table) {
                $table->increments('id');
                $table->integer('account_id')->unsigned()->default(0);
                $table->integer('client_id')->unsigned()->default(0);
                $table->integer('admin_id')->unsigned()->default(0);
                $table->string('action', 100);
                $table->string('result', 50);
                $table->text('details')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('created_at');
            });
        }
        return ['status' => 'success', 'description' => 'CentralVix AutoDJ Manager ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_autodj_manager_deactivate()
{
    return ['status' => 'success', 'description' => 'Desativado. Dados preservados.'];
}

function centralvix_autodj_manager_output($vars)
{
    if (!empty($_GET['cvix_autodj_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        CentralVixAutoDJModule::requireAdmin();
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_autodj_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixAutoDJModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cvix_save_account'])) {
        CentralVixAutoDJModule::requireAdmin();
        if (CentralVixAutoDJModule::validateCsrf($_POST['token'] ?? '')) {
            CentralVixAutoDJService::saveAccount($_POST, (int) ($_POST['id'] ?? 0));
            header('Location: ' . $vars['modulelink'] . '&tab=accounts&saved=1');
            exit;
        }
    }

    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'servers', 'accounts', 'settings', 'logs'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $modulelink = $vars['modulelink'];
    $csrfToken = CentralVixAutoDJModule::csrfToken('admin');
    $assetBase = '../modules/addons/centralvix_autodj_manager/assets';
    $ver = CentralVixAutoDJModule::VERSION;

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink($ver);
    echo '<script>var CVIX_AUTODJ=' . json_encode([
        'csrf' => $csrfToken,
        'moduleLink' => $modulelink,
        'ajaxUpload' => CentralVixAutoDJModule::ajaxUrl('upload'),
        'ajaxDelete' => CentralVixAutoDJModule::ajaxUrl('delete_media'),
        'ajaxPlaylist' => CentralVixAutoDJModule::ajaxUrl('create_playlist'),
        'ajaxSchedule' => CentralVixAutoDJModule::ajaxUrl('schedule'),
        'ajaxReorder' => CentralVixAutoDJModule::ajaxUrl('reorder'),
    ], JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<script src="' . CentralVixAutoDJSecurity::e($assetBase) . '/js/admin.js?v=' . $ver . '"></script>';

    CentralVixAutoDJModule::renderAdmin($tab, [
        'modulelink' => $modulelink,
        'tab' => $tab,
        'csrfToken' => $csrfToken,
        'stats' => CentralVixAutoDJService::dashboardAdmin(),
        'accounts' => CentralVixAutoDJService::listAccounts(),
        'providers' => CentralVixAutoDJModule::providerTypes(),
        'logs' => CentralVixAutoDJLogger::listLogs(100),
        'settings' => CentralVixAutoDJModule::moduleSettings(),
    ]);
}

function centralvix_autodj_manager_clientarea($vars)
{
    CentralVixAutoDJModule::autoload();

    if (!empty($_GET['cvix_autodj_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        CentralVixAutoDJModule::requireClient();
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_autodj_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixAutoDJModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    $clientId = (int) ($_SESSION['uid'] ?? 0);
    $page = preg_replace('/[^a-z_]/', '', $_GET['page'] ?? 'dashboard');
    $allowed = ['dashboard', 'library', 'playlists', 'schedule', 'settings'];
    if (!in_array($page, $allowed, true)) {
        $page = 'dashboard';
    }

    $account = CentralVixAutoDJService::getClientAccount($clientId);
    $csrfToken = CentralVixAutoDJModule::csrfToken('client');
    $assetBase = 'modules/addons/centralvix_autodj_manager/assets';
    $ver = CentralVixAutoDJModule::VERSION;
    $moduleLink = 'index.php?m=centralvix_autodj_manager';

    ob_start();
    CentralVixAutoDJModule::renderClient($page, [
        'account' => $account,
        'clientId' => $clientId,
        'csrfToken' => $csrfToken,
        'moduleLink' => $moduleLink,
        'page' => $page,
        'mediaTypes' => CentralVixAutoDJModule::mediaTypes(),
        'dash' => $account ? CentralVixAutoDJService::dashboardClient($account) : null,
        'media' => $account ? CentralVixAutoDJMediaLibrary::listMedia($account->id, $clientId) : [],
        'playlists' => $account ? CentralVixAutoDJPlaylistService::listPlaylists($account->id, $clientId) : [],
        'schedules' => $account ? CentralVixAutoDJScheduleService::listSchedules($account->id, $clientId) : [],
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixAutoDJModule::lang('client_title', 'Minha Rádio — AutoDJ'),
        'breadcrumb' => [$moduleLink => 'AutoDJ'],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => [
            'htmlContent' => $html,
            'assetBase' => $assetBase,
            'ver' => $ver,
            'csrf' => $csrfToken,
            'moduleLink' => $moduleLink,
            'ajaxUpload' => CentralVixAutoDJModule::ajaxUrl('upload', true),
            'ajaxDelete' => CentralVixAutoDJModule::ajaxUrl('delete_media', true),
            'ajaxPlaylist' => CentralVixAutoDJModule::ajaxUrl('create_playlist', true),
            'ajaxSchedule' => CentralVixAutoDJModule::ajaxUrl('schedule', true),
            'ajaxReorder' => CentralVixAutoDJModule::ajaxUrl('reorder', true),
        ],
    ];
}
