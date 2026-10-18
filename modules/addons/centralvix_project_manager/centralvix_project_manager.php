<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixPmModule::autoload();
CentralVixPmFileService::ensureStorage();

function centralvix_project_manager_config()
{
    return [
        'name' => 'CentralVix Project Manager',
        'description' => 'Gerenciamento de projetos: sites, apps, sistemas, integrações, infra, streaming e serviços personalizados.',
        'version' => CentralVixPmModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_upload_mb' => [
                'FriendlyName' => 'Upload máximo (MB)',
                'Type' => 'text',
                'Default' => '25',
            ],
        ],
    ];
}

function centralvix_project_manager_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_projects')) {
            Capsule::schema()->create('mod_centralvix_pm_projects', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned()->default(0);
                $table->integer('invoice_id')->unsigned()->default(0);
                $table->integer('ticket_id')->unsigned()->default(0);
                $table->string('project_name', 150);
                $table->string('project_type', 30)->default('custom');
                $table->text('description')->nullable();
                $table->string('status', 30)->default('planning');
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->integer('assigned_admin')->unsigned()->default(0);
                $table->date('start_date')->nullable();
                $table->date('deadline')->nullable();
                $table->boolean('client_visible')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('status');
                $table->index(['service_id', 'invoice_id', 'ticket_id']);
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_tasks')) {
            Capsule::schema()->create('mod_centralvix_pm_tasks', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('parent_id')->unsigned()->default(0);
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('assigned_type', 20)->default('admin');
                $table->integer('assigned_id')->unsigned()->default(0);
                $table->string('priority', 20)->default('medium');
                $table->string('status', 20)->default('todo');
                $table->integer('kanban_order')->default(0);
                $table->date('start_date')->nullable();
                $table->date('due_date')->nullable();
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->decimal('estimated_hours', 8, 2)->default(0);
                $table->decimal('used_hours', 8, 2)->default(0);
                $table->string('visibility', 20)->default('internal');
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index(['project_id', 'parent_id']);
                $table->index('status');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_comments')) {
            Capsule::schema()->create('mod_centralvix_pm_comments', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('task_id')->unsigned()->nullable();
                $table->string('author_type', 20);
                $table->integer('author_id')->unsigned()->default(0);
                $table->string('author_name', 150)->nullable();
                $table->text('message');
                $table->string('visibility', 20)->default('internal');
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_files')) {
            Capsule::schema()->create('mod_centralvix_pm_files', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('task_id')->unsigned()->nullable();
                $table->string('uploader_type', 20);
                $table->integer('uploader_id')->unsigned()->default(0);
                $table->string('stored_name', 255);
                $table->string('original_name', 255);
                $table->string('mime_type', 120)->nullable();
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('visibility', 20)->default('internal');
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_milestones')) {
            Capsule::schema()->create('mod_centralvix_pm_milestones', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->string('title', 150);
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->integer('sort_order')->default(0);
                $table->boolean('client_visible')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_time_entries')) {
            Capsule::schema()->create('mod_centralvix_pm_time_entries', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('task_id')->unsigned()->nullable();
                $table->integer('admin_id')->unsigned()->default(0);
                $table->decimal('hours', 8, 2);
                $table->string('description', 500)->nullable();
                $table->date('entry_date');
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_logs')) {
            Capsule::schema()->create('mod_centralvix_pm_logs', function ($table) {
                $table->increments('id');
                $table->integer('project_id')->unsigned();
                $table->integer('task_id')->unsigned()->nullable();
                $table->string('event_type', 50);
                $table->text('message');
                $table->string('actor_type', 20)->default('system');
                $table->integer('actor_id')->unsigned()->default(0);
                $table->text('meta_json')->nullable();
                $table->dateTime('created_at');
                $table->index('project_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_pm_settings')) {
            Capsule::schema()->create('mod_centralvix_pm_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        CentralVixPmModule::setDbSetting('email_enabled', '1');
        return ['status' => 'success', 'description' => 'CentralVix Project Manager ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_project_manager_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Dados preservados.'];
}

function centralvix_project_manager_output($vars)
{
    if (!empty($_GET['cvix_pm_download'])) {
        CentralVixPmFileService::serveDownload((int) $_GET['cvix_pm_download'], null);
    }
    if (!empty($_GET['cvix_pm_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_pm_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixPmModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'projects', 'project', 'tasks', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && CentralVixPmModule::validateCsrf($_POST['csrf_token'] ?? '')) {
        if ($tab === 'projects' && !empty($_POST['create_project'])) {
            $r = CentralVixPmProjectService::create($_POST);
            $success = $r['success'] ? 'Projeto criado.' : ($r['message'] ?? 'Erro');
            if (!$r['success']) {
                $error = $success;
                $success = '';
            }
        } elseif ($tab === 'project' && !empty($_POST['update_project'])) {
            CentralVixPmProjectService::update((int) $_POST['project_id'], $_POST);
            if (!empty($_POST['new_status'])) {
                CentralVixPmProjectService::changeStatus((int) $_POST['project_id'], $_POST['new_status'], 'admin', (int) $_SESSION['adminid']);
            }
            $success = 'Projeto atualizado.';
        } elseif ($tab === 'project' && !empty($_POST['save_milestone'])) {
            CentralVixPmMilestoneService::save($_POST, (int) ($_POST['milestone_id'] ?? 0));
            $success = 'Marco salvo.';
        } elseif ($tab === 'project' && !empty($_POST['save_time'])) {
            CentralVixPmTimeService::add($_POST);
            $success = 'Horas registradas.';
        } elseif ($tab === 'settings') {
            CentralVixPmModule::setDbSetting('email_enabled', !empty($_POST['email_enabled']) ? '1' : '0');
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixPmModule::VERSION);

    CentralVixPmModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'projectId' => (int) ($_GET['id'] ?? 0),
        'filterProject' => (int) ($_GET['project_id'] ?? 0),
        'csrf' => CentralVixPmModule::csrfToken('admin'),
        'success' => $success,
        'error' => $error,
    ]);
}

function centralvix_project_manager_clientarea($vars)
{
    if (!empty($_GET['cvix_pm_download'])) {
        CentralVixPmFileService::serveDownload((int) $_GET['cvix_pm_download'], (int) $_SESSION['uid']);
    }
    if (!empty($_GET['cvix_pm_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_pm_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixPmModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }

    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'projects');
    if (!in_array($action, ['projects', 'project', 'tasks'], true)) {
        $action = 'projects';
    }

    ob_start();
    CentralVixPmModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'action' => $action,
        'clientId' => (int) $_SESSION['uid'],
        'projectId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixPmModule::csrfToken('client'),
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixPmModule::lang('client_title', 'Meus Projetos'),
        'breadcrumb' => ['index.php?m=centralvix_project_manager' => CentralVixPmModule::lang('client_title', 'Meus Projetos')],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => ['content' => $html, 'modulelink' => $vars['modulelink']],
    ];
}
