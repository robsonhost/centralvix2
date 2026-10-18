<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixReguaModule::autoload();

function centralvix_regua_cobranca_config()
{
    return [
        'name' => 'CentralVix Régua de Cobrança',
        'description' => 'Automação de lembretes de cobrança por e-mail e WhatsApp com réguas configuráveis.',
        'version' => CentralVixReguaModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'company_name' => [
                'FriendlyName' => 'Nome da empresa',
                'Type' => 'text',
                'Size' => '50',
                'Default' => 'CentralVix',
                'Description' => 'Usado no placeholder {empresa}',
            ],
            'global_time_from' => [
                'FriendlyName' => 'Horário início envios',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '08:00',
            ],
            'global_time_to' => [
                'FriendlyName' => 'Horário fim envios',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '20:00',
            ],
            'cron_interval' => [
                'FriendlyName' => 'Intervalo cron (minutos)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '60',
            ],
            'whatsapp_endpoint' => [
                'FriendlyName' => 'WhatsApp endpoint',
                'Type' => 'text',
                'Size' => '150',
            ],
            'whatsapp_token' => [
                'FriendlyName' => 'WhatsApp token',
                'Type' => 'password',
                'Size' => '80',
            ],
            'whatsapp_instance' => [
                'FriendlyName' => 'Instância / nome',
                'Type' => 'text',
                'Size' => '50',
            ],
            'whatsapp_method' => [
                'FriendlyName' => 'Método HTTP',
                'Type' => 'dropdown',
                'Options' => 'POST,GET',
                'Default' => 'POST',
            ],
            'whatsapp_headers' => [
                'FriendlyName' => 'Headers extras (JSON)',
                'Type' => 'textarea',
                'Rows' => '3',
                'Description' => 'Ex: {"X-Api-Key":"valor"} ou ["Header: valor"]',
            ],
            'whatsapp_default_phone' => [
                'FriendlyName' => 'Telefone fallback testes',
                'Type' => 'text',
                'Size' => '20',
            ],
            'send_payment_confirmation' => [
                'FriendlyName' => 'Enviar confirmação de pagamento',
                'Type' => 'yesno',
            ],
            'payment_template_id' => [
                'FriendlyName' => 'ID template pagamento confirmado',
                'Type' => 'text',
                'Size' => '5',
            ],
            'notify_whatsapp_paid' => [
                'FriendlyName' => 'WhatsApp na confirmação de pagamento',
                'Type' => 'yesno',
            ],
        ],
    ];
}

function centralvix_regua_cobranca_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_regua_rules')) {
            Capsule::schema()->create('mod_centralvix_regua_rules', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->boolean('active')->default(1);
                $table->integer('days_relative')->unsigned()->default(0);
                $table->string('timing', 10)->default('before');
                $table->string('invoice_status', 100)->default('Unpaid');
                $table->boolean('send_email')->default(1);
                $table->boolean('send_whatsapp')->default(0);
                $table->integer('template_id')->unsigned()->default(0);
                $table->time('time_from')->default('08:00:00');
                $table->time('time_to')->default('20:00:00');
                $table->text('product_ids')->nullable();
                $table->text('product_group_ids')->nullable();
                $table->text('client_ids')->nullable();
                $table->text('client_group_ids')->nullable();
                $table->integer('display_order')->default(0);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_regua_templates')) {
            Capsule::schema()->create('mod_centralvix_regua_templates', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('subject', 200)->nullable();
                $table->text('body_email')->nullable();
                $table->text('body_whatsapp')->nullable();
                $table->boolean('is_payment_confirmation')->default(0);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_regua_logs')) {
            Capsule::schema()->create('mod_centralvix_regua_logs', function ($table) {
                $table->increments('id');
                $table->integer('invoice_id')->unsigned();
                $table->integer('rule_id')->unsigned()->default(0);
                $table->integer('client_id')->unsigned();
                $table->string('idempotency_key', 100);
                $table->string('channel', 20);
                $table->string('status', 30);
                $table->text('response')->nullable();
                $table->string('message_preview', 500)->nullable();
                $table->dateTime('executed_at');
                $table->unique(['idempotency_key', 'channel'], 'cvix_regua_idem_channel');
                $table->index(['invoice_id', 'rule_id']);
                $table->index('executed_at');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_regua_settings')) {
            Capsule::schema()->create('mod_centralvix_regua_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        CentralVixReguaTemplateService::seedDefaults();

        return ['status' => 'success', 'description' => 'CentralVix Régua de Cobrança ativada. Tabelas e réguas padrão criadas.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Erro: ' . $e->getMessage()];
    }
}

function centralvix_regua_cobranca_deactivate()
{
    return [
        'status' => 'success',
        'description' => 'Módulo desativado. Dados históricos preservados.',
    ];
}

function centralvix_regua_cobranca_output($vars)
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['cvix_save_template'])) {
        CentralVixReguaModule::requireAdmin();
        if (CentralVixReguaModule::validateCsrf($_POST['token'] ?? '')) {
            $tid = CentralVixReguaTemplateService::save($_POST, (int) ($_POST['id'] ?? 0));
            header('Location: ' . $vars['modulelink'] . '&tab=templates&edit=' . $tid . '&saved=1');
            exit;
        }
    }

    if (!empty($_GET['cvix_regua_ajax'])) {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_regua_ajax']);
        $file = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($file)) {
            require $file;
            exit;
        }
        CentralVixReguaModule::json(['success' => false, 'message' => 'AJAX inválido'], 404);
    }

    $tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', $_GET['tab']) : 'dashboard';
    $allowed = ['dashboard', 'rules', 'templates', 'history', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $modulelink = $vars['modulelink'];
    $csrfToken = CentralVixReguaModule::csrfToken();
    $assetBase = '../modules/addons/centralvix_regua_cobranca/assets';
    $ver = CentralVixReguaModule::VERSION;

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink($ver);
    echo '<script>var CVIX_REGUA=' . json_encode([
        'moduleLink' => $modulelink,
        'csrf' => $csrfToken,
        'ajaxSaveRule' => CentralVixReguaModule::ajaxUrl('save_rule'),
        'ajaxDeleteRule' => CentralVixReguaModule::ajaxUrl('delete_rule'),
        'ajaxToggleRule' => CentralVixReguaModule::ajaxUrl('toggle_rule'),
        'ajaxPreview' => CentralVixReguaModule::ajaxUrl('preview_message'),
        'ajaxTestWhatsapp' => CentralVixReguaModule::ajaxUrl('test_whatsapp'),
    ], JSON_UNESCAPED_UNICODE) . ';</script>';
    echo '<script src="' . CentralVixReguaSecurity::e($assetBase) . '/js/admin.js?v=' . $ver . '"></script>';

    CentralVixReguaModule::renderAdmin($tab, [
        'modulelink' => $modulelink,
        'tab' => $tab,
        'csrfToken' => $csrfToken,
        'stats' => CentralVixReguaLogger::dashboardCounts(),
        'rules' => CentralVixReguaBillingRuleService::listAll(),
        'templates' => CentralVixReguaTemplateService::listAll(),
        'placeholders' => CentralVixReguaTemplateService::placeholders(),
        'products' => CentralVixReguaInvoiceService::listProducts(),
        'productGroups' => CentralVixReguaInvoiceService::listProductGroups(),
        'clientGroups' => CentralVixReguaInvoiceService::listClientGroups(),
        'settings' => CentralVixReguaModule::moduleSettings(),
        'lastExecution' => CentralVixReguaModule::dbSetting('last_execution', 'Nunca'),
        'historyFilters' => [
            'invoice_id' => (int) ($_GET['invoice_id'] ?? 0),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'rule_id' => (int) ($_GET['rule_id'] ?? 0),
            'channel' => preg_replace('/[^a-z]/', '', $_GET['channel'] ?? ''),
            'status' => preg_replace('/[^a-z_]/', '', $_GET['status'] ?? ''),
            'date_from' => preg_replace('/[^0-9\-]/', '', $_GET['date_from'] ?? ''),
            'date_to' => preg_replace('/[^0-9\-]/', '', $_GET['date_to'] ?? ''),
        ],
        'historyPage' => max(1, (int) ($_GET['page'] ?? 1)),
        'history' => CentralVixReguaLogger::listLogs([
            'invoice_id' => (int) ($_GET['invoice_id'] ?? 0),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'rule_id' => (int) ($_GET['rule_id'] ?? 0),
            'channel' => preg_replace('/[^a-z]/', '', $_GET['channel'] ?? ''),
            'status' => preg_replace('/[^a-z_]/', '', $_GET['status'] ?? ''),
            'date_from' => preg_replace('/[^0-9\-]/', '', $_GET['date_from'] ?? ''),
            'date_to' => preg_replace('/[^0-9\-]/', '', $_GET['date_to'] ?? ''),
        ], max(1, (int) ($_GET['page'] ?? 1)), 50),
    ]);
}
