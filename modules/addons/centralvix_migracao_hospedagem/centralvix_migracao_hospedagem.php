<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixMigModule::autoload();
CentralVixMigFileService::ensureStorage();

function centralvix_migracao_hospedagem_config()
{
    return [
        'name' => 'CentralVix Migração de Hospedagem',
        'description' => 'Central de solicitações de migração de hospedagem — fluxo, checklist, credenciais criptografadas e timeline cliente.',
        'version' => CentralVixMigModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_upload_mb' => [
                'FriendlyName' => 'Upload máximo cliente (MB)',
                'Type' => 'text',
                'Default' => '25',
            ],
        ],
    ];
}

function centralvix_migracao_hospedagem_activate()
{
    try {
        CentralVixMigModule::ensureStorageDirs();

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_requests')) {
            Capsule::schema()->create('mod_centralvix_migration_requests', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->string('reference', 30)->unique();
                $table->string('domain', 150);
                $table->string('current_hosting', 150)->nullable();
                $table->string('current_panel', 30)->default('outro');
                $table->string('current_server', 150)->nullable();
                $table->string('migration_type', 80)->default('completa');
                $table->string('estimated_size', 50)->nullable();
                $table->unsignedInteger('database_count')->default(0);
                $table->unsignedInteger('email_count')->default(0);
                $table->text('notes')->nullable();
                $table->date('preferred_date')->nullable();
                $table->string('status', 30)->default('solicitada');
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->text('client_message')->nullable();
                $table->text('admin_notes')->nullable();
                $table->text('credentials_blob')->nullable();
                $table->boolean('credentials_provided')->default(0);
                $table->dateTime('credentials_purged_at')->nullable();
                $table->dateTime('scheduled_date')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('status');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_checklist')) {
            Capsule::schema()->create('mod_centralvix_migration_checklist', function ($table) {
                $table->increments('id');
                $table->integer('request_id')->unsigned();
                $table->string('item_key', 50);
                $table->string('label', 120);
                $table->boolean('completed')->default(0);
                $table->dateTime('completed_at')->nullable();
                $table->integer('completed_by')->unsigned()->default(0);
                $table->string('notes', 500)->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->unique(['request_id', 'item_key']);
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_comments')) {
            Capsule::schema()->create('mod_centralvix_migration_comments', function ($table) {
                $table->increments('id');
                $table->integer('request_id')->unsigned();
                $table->string('author_type', 20);
                $table->integer('author_id')->unsigned()->default(0);
                $table->string('author_name', 150)->nullable();
                $table->text('message');
                $table->string('visibility', 20)->default('client');
                $table->dateTime('created_at');
                $table->index('request_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_files')) {
            Capsule::schema()->create('mod_centralvix_migration_files', function ($table) {
                $table->increments('id');
                $table->integer('request_id')->unsigned();
                $table->string('uploader_type', 20);
                $table->integer('uploader_id')->unsigned()->default(0);
                $table->string('stored_name', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('file_type', 20)->default('doc');
                $table->dateTime('created_at');
                $table->index('request_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_logs')) {
            Capsule::schema()->create('mod_centralvix_migration_logs', function ($table) {
                $table->increments('id');
                $table->integer('request_id')->unsigned();
                $table->string('action', 50);
                $table->text('message');
                $table->string('actor_type', 20)->default('system');
                $table->integer('actor_id')->unsigned()->default(0);
                $table->string('ip_address', 45)->nullable();
                $table->text('context_json')->nullable();
                $table->dateTime('created_at');
                $table->index('request_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_migration_settings')) {
            Capsule::schema()->create('mod_centralvix_migration_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        CentralVixMigCredentialVault::ensureMasterKey();
        CentralVixMigModule::setDbSetting('email_enabled', '1');
        CentralVixMigModule::setDbSetting('whatsapp_enabled', '0');
        CentralVixMigModule::setDbSetting('auto_purge_credentials', '1');
        CentralVixMigModule::setDbSetting('allow_client_backup_upload', '0');
        CentralVixMigModule::setDbSetting('migration_provider', 'manual');

        return ['status' => 'success', 'description' => 'CentralVix Migração de Hospedagem ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_migracao_hospedagem_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Solicitações e histórico preservados.'];
}

function centralvix_migracao_hospedagem_output($vars)
{
    if (!empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        CentralVixMigFileService::serveDownload((int) $_GET[CentralVixMigModule::DOWNLOAD_PARAM], null);
    }
    if (!empty($_GET[CentralVixMigModule::AJAX_PARAM])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET[CentralVixMigModule::AJAX_PARAM]);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixMigModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'requests', 'request', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && CentralVixMigModule::validateCsrf($_POST['csrf_token'] ?? '')) {
        if ($tab === 'request' && !empty($_POST['admin_action'])) {
            $requestId = (int) ($_POST['request_id'] ?? 0);
            $act = $_POST['admin_action'];
            if ($act === 'status') {
                $r = CentralVixMigMigrationService::changeStatus(
                    $requestId,
                    $_POST['new_status'] ?? '',
                    'admin',
                    CentralVixMigSecurity::adminId(),
                    $_POST['client_message'] ?? ''
                );
                $success = $r['success'] ? 'Status atualizado.' : ($r['message'] ?? 'Erro');
                if (!$r['success']) {
                    $error = $success;
                    $success = '';
                }
            } elseif ($act === 'schedule') {
                $r = CentralVixMigMigrationService::schedule($requestId, $_POST['scheduled_date'] ?? '', CentralVixMigSecurity::adminId());
                $success = $r['success'] ? 'Migração agendada.' : ($r['message'] ?? 'Erro');
                if (!$r['success']) {
                    $error = $success;
                    $success = '';
                }
            } elseif ($act === 'admin_notes') {
                Capsule::table('mod_centralvix_migration_requests')->where('id', $requestId)->update([
                    'admin_notes' => CentralVixMigSecurity::sanitizeHtml($_POST['admin_notes'] ?? '', 5000),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $success = 'Notas internas salvas.';
            } elseif ($act === 'credentials') {
                $r = CentralVixMigCredentialVault::store($requestId, [
                    'host' => $_POST['cred_host'] ?? '',
                    'port' => $_POST['cred_port'] ?? 2083,
                    'username' => $_POST['cred_username'] ?? '',
                    'password' => $_POST['cred_password'] ?? '',
                ]);
                $success = $r['success'] ? 'Credenciais salvas (criptografadas).' : ($r['message'] ?? 'Erro');
                if (!$r['success']) {
                    $error = $success;
                    $success = '';
                }
            } elseif ($act === 'purge_credentials') {
                CentralVixMigCredentialVault::purge($requestId, 'admin_manual');
                $success = 'Credenciais excluídas.';
            } elseif ($act === 'checklist') {
                CentralVixMigChecklistService::toggle(
                    $requestId,
                    $_POST['item_key'] ?? '',
                    !empty($_POST['completed']),
                    CentralVixMigSecurity::adminId(),
                    $_POST['notes'] ?? ''
                );
                $success = 'Checklist atualizado.';
            }
        } elseif ($tab === 'settings') {
            CentralVixMigModule::setDbSetting('email_enabled', !empty($_POST['email_enabled']) ? '1' : '0');
            CentralVixMigModule::setDbSetting('whatsapp_enabled', !empty($_POST['whatsapp_enabled']) ? '1' : '0');
            CentralVixMigModule::setDbSetting('auto_purge_credentials', !empty($_POST['auto_purge_credentials']) ? '1' : '0');
            CentralVixMigModule::setDbSetting('allow_client_backup_upload', !empty($_POST['allow_client_backup_upload']) ? '1' : '0');
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixMigModule::VERSION);
    CentralVixMigModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'requestId' => (int) ($_GET['id'] ?? 0),
        'filterStatus' => preg_replace('/[^a-z_]/', '', $_GET['status'] ?? ''),
        'csrf' => CentralVixMigModule::csrfToken('admin'),
        'success' => $success,
        'error' => $error,
    ]);
}

function centralvix_migracao_hospedagem_clientarea($vars)
{
    if (!empty($_GET[CentralVixMigModule::DOWNLOAD_PARAM])) {
        CentralVixMigFileService::serveDownload((int) $_GET[CentralVixMigModule::DOWNLOAD_PARAM], (int) $_SESSION['uid']);
    }
    if (!empty($_GET[CentralVixMigModule::AJAX_PARAM])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET[CentralVixMigModule::AJAX_PARAM]);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixMigModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'requests');
    if (!in_array($action, ['requests', 'new', 'request'], true)) {
        $action = 'requests';
    }

    ob_start();
    echo '<link rel="stylesheet" href="modules/addons/centralvix_migracao_hospedagem/assets/css/client.css?v=' . CentralVixMigModule::VERSION . '">';
    echo '<script src="modules/addons/centralvix_migracao_hospedagem/assets/js/client.js?v=' . CentralVixMigModule::VERSION . '"></script>';
    CentralVixMigModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'action' => $action,
        'clientId' => (int) $_SESSION['uid'],
        'requestId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixMigModule::csrfToken('client'),
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixMigModule::lang('client_title', 'Migração de Hospedagem'),
        'breadcrumb' => ['index.php?m=centralvix_migracao_hospedagem' => CentralVixMigModule::lang('client_title', 'Migração de Hospedagem')],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => ['content' => $html, 'modulelink' => $vars['modulelink']],
    ];
}
