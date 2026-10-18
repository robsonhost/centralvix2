<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixLicModule::autoload();

function centralvix_licenciamento_config()
{
    return [
        'name' => 'CentralVix Licenciamento de Sistemas',
        'description' => 'Gerenciamento de licenças, domínios, IPs, validação API e integração WHMCS.',
        'version' => CentralVixLicModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'auto_provision' => [
                'FriendlyName' => 'Provisionar licença ao criar serviço',
                'Type' => 'yesno',
            ],
        ],
    ];
}

function centralvix_licenciamento_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_license_products')) {
            Capsule::schema()->create('mod_centralvix_license_products', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('slug', 80)->nullable();
                $table->integer('whmcs_product_id')->unsigned()->default(0);
                $table->unsignedInteger('max_activations')->default(1);
                $table->unsignedInteger('grace_period_hours')->default(24);
                $table->unsignedInteger('validity_days')->default(365);
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_licenses')) {
            Capsule::schema()->create('mod_centralvix_licenses', function ($table) {
                $table->increments('id');
                $table->string('license_key', 64);
                $table->string('license_key_hash', 64)->unique();
                $table->integer('client_id')->unsigned();
                $table->integer('service_id')->unsigned()->default(0);
                $table->integer('product_id')->unsigned();
                $table->string('domain', 150)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('status', 20)->default('pendente');
                $table->dateTime('issued_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->dateTime('last_check')->nullable();
                $table->unsignedInteger('max_activations')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('service_id');
                $table->index('status');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_license_activations')) {
            Capsule::schema()->create('mod_centralvix_license_activations', function ($table) {
                $table->increments('id');
                $table->integer('license_id')->unsigned();
                $table->string('domain', 150);
                $table->string('ip_address', 45);
                $table->string('status', 20)->default('active');
                $table->dateTime('activated_at');
                $table->dateTime('last_seen')->nullable();
                $table->dateTime('revoked_at')->nullable();
                $table->index('license_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_license_logs')) {
            Capsule::schema()->create('mod_centralvix_license_logs', function ($table) {
                $table->increments('id');
                $table->integer('license_id')->unsigned()->nullable();
                $table->integer('client_id')->unsigned()->default(0);
                $table->string('action', 50);
                $table->text('message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('context_json')->nullable();
                $table->dateTime('created_at');
                $table->index('license_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_license_settings')) {
            Capsule::schema()->create('mod_centralvix_license_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        CentralVixLicEncryptionService::ensurePepper();
        CentralVixLicEncryptionService::ensureApiSecret();
        CentralVixLicModule::setDbSetting('default_grace_hours', '24');
        CentralVixLicModule::setDbSetting('api_rate_limit', '60');
        CentralVixLicModule::setDbSetting('email_enabled', '1');
        return ['status' => 'success', 'description' => 'CentralVix Licenciamento ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_licenciamento_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Dados preservados.'];
}

function centralvix_licenciamento_output($vars)
{
    CentralVixLicLicenseService::checkExpired();
    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'licenses', 'products', 'settings', 'logs'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && CentralVixLicModule::validateCsrf($_POST['csrf_token'] ?? '')) {
        $action = $_POST['admin_action'] ?? '';
        if ($action === 'create_license') {
            $r = CentralVixLicLicenseService::create($_POST);
            $success = $r['success'] ? 'Licença criada: ' . ($r['license_key'] ?? '') : ($r['message'] ?? 'Erro');
            if (!$r['success']) {
                $error = $success;
                $success = '';
            }
        } elseif ($action === 'save_product') {
            CentralVixLicProductService::save($_POST, (int) ($_POST['product_id'] ?? 0));
            $success = 'Produto salvo.';
        } elseif ($action === 'license_action') {
            $id = (int) ($_POST['license_id'] ?? 0);
            $act = $_POST['license_act'] ?? '';
            switch ($act) {
                case 'activate': CentralVixLicLicenseService::setStatus($id, 'ativa', 'Ativada pelo admin'); $success = 'Ativada.'; break;
                case 'suspend': CentralVixLicLicenseService::setStatus($id, 'suspensa', 'Suspensa pelo admin'); $success = 'Suspensa.'; break;
                case 'cancel': CentralVixLicLicenseService::setStatus($id, 'cancelada', 'Cancelada pelo admin'); $success = 'Cancelada.'; break;
                case 'renew': CentralVixLicLicenseService::renew($id, (int) ($_POST['renew_days'] ?? 365)); $success = 'Renovada.'; break;
                case 'domain': CentralVixLicLicenseService::changeDomain($id, $_POST['new_domain'] ?? ''); $success = 'Domínio atualizado.'; break;
                case 'reset': CentralVixLicLicenseService::resetActivations($id); $success = 'Ativações resetadas.'; break;
            }
        } elseif ($tab === 'settings') {
            CentralVixLicModule::setDbSetting('default_grace_hours', (int) ($_POST['default_grace_hours'] ?? 24));
            CentralVixLicModule::setDbSetting('api_rate_limit', (int) ($_POST['api_rate_limit'] ?? 60));
            CentralVixLicModule::setDbSetting('email_enabled', !empty($_POST['email_enabled']) ? '1' : '0');
            if (!empty($_POST['regenerate_api_secret'])) {
                CentralVixLicModule::setDbSetting('api_secret', bin2hex(random_bytes(32)));
            }
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixLicModule::VERSION);

    CentralVixLicModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'licenseId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixLicModule::csrfToken('admin'),
        'success' => $success,
        'error' => $error,
    ]);
}

function centralvix_licenciamento_clientarea($vars)
{
    CentralVixLicLicenseService::checkExpired();
    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'licenses');
    if (!in_array($action, ['licenses', 'license'], true)) {
        $action = 'licenses';
    }
    ob_start();
    CentralVixLicModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'clientId' => (int) $_SESSION['uid'],
        'licenseId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixLicModule::csrfToken('client'),
    ]);
    return [
        'pagetitle' => CentralVixLicModule::lang('client_title', 'Minhas Licenças'),
        'breadcrumb' => ['index.php?m=centralvix_licenciamento' => CentralVixLicModule::lang('client_title', 'Minhas Licenças')],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => ['content' => ob_get_clean(), 'modulelink' => $vars['modulelink']],
    ];
}
