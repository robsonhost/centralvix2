<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixUpdModule::autoload();

function centralvix_updater_config()
{
    return [
        'name' => 'CentralVix Atualizador Automático',
        'description' => 'Servidor central de atualização para sistemas CentralVix — versões, pacotes, download autenticado e API.',
        'version' => CentralVixUpdModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'api_enabled' => [
                'FriendlyName' => 'API de atualização ativa',
                'Type' => 'yesno',
                'Description' => 'Desative para bloquear check/download via API.',
            ],
        ],
    ];
}

function centralvix_updater_activate()
{
    try {
        CentralVixUpdModule::ensureStorageDirs();

        if (!Capsule::schema()->hasTable('mod_centralvix_updater_products')) {
            Capsule::schema()->create('mod_centralvix_updater_products', function ($table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('slug', 80)->unique();
                $table->text('description')->nullable();
                $table->boolean('require_license')->default(0);
                $table->string('license_product_slug', 80)->nullable();
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_updater_versions')) {
            Capsule::schema()->create('mod_centralvix_updater_versions', function ($table) {
                $table->increments('id');
                $table->integer('product_id')->unsigned();
                $table->string('version', 30);
                $table->string('channel', 20)->default('stable');
                $table->text('release_notes')->nullable();
                $table->string('file_path', 255)->default('');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('sha256_hash', 64)->default('');
                $table->string('signature', 64)->default('');
                $table->string('min_version', 30)->default('0.0.0');
                $table->string('min_php', 20)->default('7.4');
                $table->string('min_whmcs', 20)->nullable();
                $table->boolean('is_required')->default(0);
                $table->boolean('active')->default(1);
                $table->dateTime('released_at')->nullable();
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index(['product_id', 'channel']);
                $table->unique(['product_id', 'version', 'channel']);
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_updater_downloads')) {
            Capsule::schema()->create('mod_centralvix_updater_downloads', function ($table) {
                $table->increments('id');
                $table->integer('version_id')->unsigned()->default(0);
                $table->integer('product_id')->unsigned()->default(0);
                $table->string('token_hash', 64)->nullable();
                $table->string('license_key_hash', 64)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('status', 30);
                $table->string('message', 500)->nullable();
                $table->text('context_json')->nullable();
                $table->dateTime('created_at');
                $table->index('status');
                $table->index('product_id');
            });
        }

        if (!Capsule::schema()->hasTable('mod_centralvix_updater_settings')) {
            Capsule::schema()->create('mod_centralvix_updater_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        CentralVixUpdSignatureService::ensureSigningSecret();
        CentralVixUpdModule::setDbSetting('api_rate_limit', '60');
        CentralVixUpdModule::setDbSetting('download_token_ttl', '15');
        CentralVixUpdModule::setDbSetting('license_pepper', bin2hex(random_bytes(16)));

        return ['status' => 'success', 'description' => 'CentralVix Atualizador ativado. Configure produtos e versões.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_updater_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Versões e pacotes preservados.'];
}

function centralvix_updater_output($vars)
{
    $modulelink = $vars['modulelink'] ?? '';
    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'products', 'versions', 'upload', 'downloads', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && CentralVixUpdModule::validateCsrf($_POST['csrf_token'] ?? '')) {
        $action = $_POST['admin_action'] ?? '';
        if ($action === 'save_product') {
            $r = CentralVixUpdProductService::save($_POST, (int) ($_POST['product_id'] ?? 0));
            if ($r['success']) {
                $success = 'Produto salvo.';
            } else {
                $error = $r['message'] ?? 'Erro ao salvar produto.';
            }
        } elseif ($action === 'save_version') {
            $r = CentralVixUpdVersionService::saveMeta($_POST, (int) ($_POST['version_id'] ?? 0));
            if ($r['success']) {
                $success = 'Versão salva.';
            } else {
                $error = $r['message'] ?? 'Erro ao salvar versão.';
            }
        } elseif ($action === 'upload_package') {
            $r = CentralVixUpdPackageService::handleUpload((int) ($_POST['version_id'] ?? 0), $_FILES['package'] ?? []);
            if ($r['success']) {
                $success = 'Pacote enviado. SHA-256: ' . ($r['sha256'] ?? '');
            } else {
                $error = $r['message'] ?? 'Falha no upload.';
            }
        } elseif ($action === 'save_settings') {
            CentralVixUpdModule::setDbSetting('api_rate_limit', max(1, (int) ($_POST['api_rate_limit'] ?? 60)));
            CentralVixUpdModule::setDbSetting('download_token_ttl', max(5, (int) ($_POST['download_token_ttl'] ?? 15)));
            CentralVixUpdLogger::log('settings_save', 'Configurações atualizadas');
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixUpdModule::VERSION);
    CentralVixUpdModule::renderAdmin($tab, compact('modulelink', 'tab', 'success', 'error'));
}
