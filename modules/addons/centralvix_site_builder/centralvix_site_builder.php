<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixSiteModule::autoload();
CentralVixSiteFileService::ensureStorage();

function centralvix_site_builder_config()
{
    return [
        'name' => 'CentralVix Site Builder',
        'description' => 'Central de produção e entrega de sites: briefing, materiais, design, revisão, aprovação e publicação.',
        'version' => CentralVixSiteModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_upload_mb' => [
                'FriendlyName' => 'Upload máximo (MB)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '25',
            ],
            'auto_create_on_order' => [
                'FriendlyName' => 'Criar projeto ao ativar serviço',
                'Type' => 'yesno',
                'Description' => 'Cria projeto automaticamente quando um serviço hosting/site é provisionado (requer hook).',
            ],
        ],
    ];
}

function centralvix_site_builder_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_site_projects')) {
            Capsule::schema()->create('mod_centralvix_site_projects', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned()->default(0);
                $table->string('product_name', 150)->nullable();
                $table->string('project_name', 150);
                $table->string('domain', 150)->nullable();
                $table->string('plan', 100)->nullable();
                $table->integer('assigned_admin')->unsigned()->default(0);
                $table->date('start_date')->nullable();
                $table->date('deadline')->nullable();
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->string('status', 40)->default('aguardando_briefing');
                $table->string('current_stage', 40)->default('aguardando_briefing');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('status');
                $table->index('deadline');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_briefings')) {
            Capsule::schema()->create('mod_centralvix_site_briefings', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned()->unique();
                $table->string('company_name', 200)->nullable();
                $table->string('segment', 150)->nullable();
                $table->text('description')->nullable();
                $table->text('objective')->nullable();
                $table->text('audience')->nullable();
                $table->text('pages')->nullable();
                $table->string('colors', 500)->nullable();
                $table->text('reference_sites')->nullable();
                $table->text('social_networks')->nullable();
                $table->text('contact_info')->nullable();
                $table->text('notes')->nullable();
                $table->text('custom_fields_json')->nullable();
                $table->dateTime('submitted_at')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_files')) {
            Capsule::schema()->create('mod_centralvix_site_files', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('uploader_type', 20);
                $table->integer('uploader_id')->unsigned()->default(0);
                $table->string('file_category', 30);
                $table->string('stored_name', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->dateTime('created_at');
                $table->index(['project_id', 'client_id']);
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_comments')) {
            Capsule::schema()->create('mod_centralvix_site_comments', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('file_id')->unsigned()->nullable();
                $table->string('author_type', 20);
                $table->integer('author_id')->unsigned()->default(0);
                $table->string('author_name', 150)->nullable();
                $table->text('message');
                $table->dateTime('created_at');
                $table->index('project_id');
                $table->index('file_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_approvals')) {
            Capsule::schema()->create('mod_centralvix_site_approvals', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('stage', 50);
                $table->string('action', 30);
                $table->text('message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_timeline')) {
            Capsule::schema()->create('mod_centralvix_site_timeline', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->string('event_type', 50);
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('actor_type', 20)->default('system');
                $table->integer('actor_id')->unsigned()->default(0);
                $table->string('actor_name', 150)->nullable();
                $table->text('meta_json')->nullable();
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_site_settings')) {
            Capsule::schema()->create('mod_centralvix_site_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        CentralVixSiteModule::setDbSetting('email_enabled', '1');
        CentralVixSiteModule::setDbSetting('whatsapp_enabled', '');
        CentralVixSiteModule::setDbSetting('briefing_custom_fields', '[]');
        return ['status' => 'success', 'description' => 'CentralVix Site Builder ativado com sucesso.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Erro na ativação: ' . $e->getMessage()];
    }
}

function centralvix_site_builder_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Dados preservados.'];
}

function centralvix_site_builder_output($vars)
{
    if (!empty($_GET['cvix_site_download'])) {
        CentralVixSiteFileService::serveDownload((int) $_GET['cvix_site_download'], null, true);
    }
    if (!empty($_GET['cvix_site_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_site_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixSiteModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'projects', 'project', 'templates', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!CentralVixSiteModule::validateCsrf($_POST['csrf_token'] ?? '')) {
            $error = 'Token CSRF inválido.';
        } elseif ($tab === 'projects' && !empty($_POST['create_project'])) {
            $r = CentralVixSiteProjectService::create($_POST);
            $success = $r['success'] ? 'Projeto criado.' : ($r['message'] ?? 'Erro');
            if (!$r['success']) {
                $error = $success;
                $success = '';
            }
        } elseif ($tab === 'project' && !empty($_POST['update_project'])) {
            $r = CentralVixSiteProjectService::update((int) $_POST['project_id'], $_POST);
            $success = $r['success'] ? 'Projeto atualizado.' : ($r['message'] ?? 'Erro');
        } elseif ($tab === 'settings') {
            CentralVixSiteModule::setDbSetting('email_enabled', !empty($_POST['email_enabled']) ? '1' : '0');
            CentralVixSiteModule::setDbSetting('whatsapp_enabled', !empty($_POST['whatsapp_enabled']) ? '1' : '0');
            $custom = [];
            if (!empty($_POST['custom_field_key']) && is_array($_POST['custom_field_key'])) {
                foreach ($_POST['custom_field_key'] as $i => $key) {
                    $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
                    $label = CentralVixSiteSecurity::sanitizeText($_POST['custom_field_label'][$i] ?? '', 100);
                    if ($key && $label) {
                        $custom[] = ['key' => $key, 'label' => $label];
                    }
                }
            }
            CentralVixSiteModule::setDbSetting('briefing_custom_fields', json_encode($custom, JSON_UNESCAPED_UNICODE));
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixSiteModule::VERSION);

    CentralVixSiteModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'projectId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixSiteModule::csrfToken('admin'),
        'success' => $success,
        'error' => $error,
    ]);
}

function centralvix_site_builder_clientarea($vars)
{
    if (!empty($_GET['cvix_site_download'])) {
        CentralVixSiteFileService::serveDownload((int) $_GET['cvix_site_download'], (int) $_SESSION['uid'], false);
    }
    if (!empty($_GET['cvix_site_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_site_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixSiteModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'projects');
    $allowed = ['projects', 'project', 'briefing', 'files', 'approval'];
    if (!in_array($action, $allowed, true)) {
        $action = 'projects';
    }

    ob_start();
    CentralVixSiteModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'action' => $action,
        'clientId' => (int) $_SESSION['uid'],
        'projectId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixSiteModule::csrfToken('client'),
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixSiteModule::lang('client_title', 'Meus Sites'),
        'breadcrumb' => [
            'index.php?m=centralvix_site_builder' => CentralVixSiteModule::lang('client_title', 'Meus Sites'),
        ],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => [
            'content' => $html,
            'modulelink' => $vars['modulelink'],
        ],
    ];
}
