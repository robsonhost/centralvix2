<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Bootstrap.php';
CentralVixStatusModule::autoload();

function centralvix_status_page_config()
{
    return [
        'name' => 'CentralVix Status Page',
        'description' => 'Central de status público e monitoramento de serviços da CentralVix integrada ao WHMCS.',
        'version' => CentralVixStatusModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'notify_email' => [
                'FriendlyName' => 'Notificações por e-mail',
                'Type' => 'yesno',
                'Description' => 'Enviar alertas por e-mail',
            ],
            'notify_email_to' => [
                'FriendlyName' => 'E-mail destino',
                'Type' => 'text',
                'Size' => '50',
                'Description' => 'E-mail para alertas',
            ],
            'notify_whatsapp' => [
                'FriendlyName' => 'Notificações WhatsApp',
                'Type' => 'yesno',
            ],
            'whatsapp_endpoint' => [
                'FriendlyName' => 'WhatsApp endpoint',
                'Type' => 'text',
                'Size' => '100',
            ],
            'whatsapp_token' => [
                'FriendlyName' => 'WhatsApp token',
                'Type' => 'password',
                'Size' => '50',
            ],
            'whatsapp_instance' => [
                'FriendlyName' => 'Nome da instância',
                'Type' => 'text',
                'Size' => '50',
            ],
            'whatsapp_phone' => [
                'FriendlyName' => 'Telefone WhatsApp',
                'Type' => 'text',
                'Size' => '20',
            ],
            'notify_cooldown' => [
                'FriendlyName' => 'Cooldown notificações (min)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
            ],
            'allow_private_targets' => [
                'FriendlyName' => 'Permitir IPs privados nos monitores',
                'Type' => 'yesno',
                'Description' => 'Desabilitado por padrão (proteção SSRF)',
            ],
            'check_interval' => [
                'FriendlyName' => 'Intervalo de checagem (min)',
                'Type' => 'text',
                'Default' => '5',
            ],
        ],
    ];
}

function centralvix_status_page_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_status_services')) {
            Capsule::schema()->create('mod_centralvix_status_services', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('category', 50)->default('custom');
                $table->string('monitor_type', 30)->default('https');
                $table->string('host', 255)->nullable();
                $table->integer('port')->unsigned()->default(0);
                $table->string('url', 500)->nullable();
                $table->integer('expected_http_code')->default(200);
                $table->integer('timeout')->default(10);
                $table->string('status', 50)->default('operational');
                $table->decimal('uptime_percentage', 5, 2)->default(100);
                $table->integer('response_time')->default(0);
                $table->boolean('is_public')->default(1);
                $table->integer('display_order')->default(0);
                $table->boolean('enabled')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_checks')) {
            Capsule::schema()->create('mod_centralvix_status_checks', function ($table) {
                $table->increments('id');
                $table->integer('service_id')->unsigned();
                $table->string('status', 50);
                $table->integer('response_time')->default(0);
                $table->string('message', 500)->nullable();
                $table->dateTime('checked_at');
                $table->index(['service_id', 'checked_at']);
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_incidents')) {
            Capsule::schema()->create('mod_centralvix_status_incidents', function ($table) {
                $table->increments('id');
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->string('severity', 50)->default('partial_outage');
                $table->text('affected_services')->nullable();
                $table->string('status', 50)->default('investigating');
                $table->dateTime('started_at');
                $table->dateTime('resolved_at')->nullable();
                $table->boolean('is_public')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_incident_updates')) {
            Capsule::schema()->create('mod_centralvix_status_incident_updates', function ($table) {
                $table->increments('id');
                $table->integer('incident_id')->unsigned();
                $table->string('status', 50);
                $table->text('message')->nullable();
                $table->integer('admin_id')->unsigned()->default(0);
                $table->dateTime('created_at');
                $table->index('incident_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_maintenance')) {
            Capsule::schema()->create('mod_centralvix_status_maintenance', function ($table) {
                $table->increments('id');
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->text('affected_services')->nullable();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->string('status', 50)->default('scheduled');
                $table->boolean('is_public')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_subscribers')) {
            Capsule::schema()->create('mod_centralvix_status_subscribers', function ($table) {
                $table->increments('id');
                $table->string('email', 150)->nullable();
                $table->string('phone', 30)->nullable();
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_settings')) {
            Capsule::schema()->create('mod_centralvix_status_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_status_history')) {
            Capsule::schema()->create('mod_centralvix_status_history', function ($table) {
                $table->increments('id');
                $table->integer('admin_id')->unsigned()->default(0);
                $table->string('action', 100);
                $table->integer('service_id')->unsigned()->default(0);
                $table->string('ip_address', 45)->nullable();
                $table->string('result', 50)->nullable();
                $table->text('details')->nullable();
                $table->dateTime('created_at');
                $table->index('created_at');
            });
        }

        return ['status' => 'success', 'description' => 'CentralVix Status Page ativado. Tabelas criadas.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Erro: ' . $e->getMessage()];
    }
}

function centralvix_status_page_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desativado. Dados históricos preservados.',
    ];
}

function centralvix_status_page_output($vars)
{
    if (!empty($_GET['cvix_status_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_status_ajax']);
        $file = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($file)) {
            require $file;
            exit;
        }
        CentralVixStatusModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    $tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', $_GET['tab']) : 'dashboard';
    $allowed = ['dashboard', 'services', 'monitors', 'incidents', 'maintenance', 'history', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $modulelink = $vars['modulelink'];
    $csrfToken = CentralVixStatusModule::csrfToken();
    $assetBase = '../modules/addons/centralvix_status_page/assets';
    $ver = CentralVixStatusModule::VERSION;

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink($ver);
    echo '<script>var CVIX_STATUS=' . json_encode([
        'moduleLink' => $modulelink,
        'csrf' => $csrfToken,
        'ajaxCheck' => CentralVixStatusModule::ajaxUrl('check'),
        'ajaxSaveService' => CentralVixStatusModule::ajaxUrl('save_service'),
        'ajaxSaveIncident' => CentralVixStatusModule::ajaxUrl('save_incident'),
        'ajaxSaveMaintenance' => CentralVixStatusModule::ajaxUrl('save_maintenance'),
        'ajaxUpdateStatus' => CentralVixStatusModule::ajaxUrl('update_status'),
        'ajaxRunAll' => CentralVixStatusModule::ajaxUrl('run_all'),
        'publicUrl' => CentralVixStatusModule::publicStatusUrl(),
    ], JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<script src="' . $assetBase . '/js/admin.js?v=' . $ver . '"></script>';

    CentralVixStatusModule::renderAdmin($tab, [
        'modulelink' => $modulelink,
        'tab' => $tab,
        'csrfToken' => $csrfToken,
        'stats' => CentralVixStatusService::dashboardStats(),
        'statuses' => CentralVixStatusModule::getStatuses(),
        'categories' => CentralVixStatusModule::getCategories(),
        'monitorTypes' => CentralVixStatusModule::getMonitorTypes(),
        'settings' => CentralVixStatusModule::moduleSettings(),
        'overall' => CentralVixStatusService::getOverallStatus(),
        'chart24' => CentralVixStatusUptimeService::getAggregateChart('24h'),
        'chart7' => CentralVixStatusUptimeService::getAggregateChart('7d'),
        'chart30' => CentralVixStatusUptimeService::getAggregateChart('30d'),
    ]);
}
