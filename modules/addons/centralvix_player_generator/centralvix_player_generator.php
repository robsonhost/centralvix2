<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixPlayerModule::autoload();
CentralVixPlayerService::ensureStorage();

function centralvix_player_generator_config()
{
    return [
        'name' => 'CentralVix Player Generator',
        'description' => 'Gerador de players personalizados para rádios e streams com embed, iframe e URL pública.',
        'version' => CentralVixPlayerModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_image_mb' => [
                'FriendlyName' => 'Upload máximo imagem (MB)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '2',
            ],
            'allow_localhost_streams' => [
                'FriendlyName' => 'Permitir streams localhost (dev)',
                'Type' => 'yesno',
            ],
            'default_iframe_width' => [
                'FriendlyName' => 'Largura iframe padrão',
                'Type' => 'text',
                'Default' => '360',
            ],
            'default_iframe_height' => [
                'FriendlyName' => 'Altura iframe padrão',
                'Type' => 'text',
                'Default' => '420',
            ],
        ],
    ];
}

function centralvix_player_generator_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_players')) {
            Capsule::schema()->create('mod_centralvix_players', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->string('public_hash', 64)->unique();
                $table->string('radio_name', 150);
                $table->string('stream_url', 500);
                $table->string('stream_type', 20)->default('audio');
                $table->string('metadata_adapter', 30)->default('auto');
                $table->string('logo_path', 500)->nullable();
                $table->string('bg_image_path', 500)->nullable();
                $table->string('primary_color', 20)->default('#1e4fd8');
                $table->string('secondary_color', 20)->default('#00bcd4');
                $table->string('custom_text', 300)->nullable();
                $table->string('theme_mode', 10)->default('dark');
                $table->boolean('show_play')->default(1);
                $table->boolean('show_volume')->default(1);
                $table->boolean('show_track')->default(1);
                $table->boolean('show_artist')->default(1);
                $table->boolean('show_cover')->default(1);
                $table->boolean('show_listeners')->default(1);
                $table->boolean('show_socials')->default(1);
                $table->boolean('active')->default(1);
                $table->integer('theme_id')->unsigned()->default(0);
                $table->unsignedBigInteger('views_count')->default(0);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('active');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_player_themes')) {
            Capsule::schema()->create('mod_centralvix_player_themes', function ($table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('primary_color', 20);
                $table->string('secondary_color', 20);
                $table->string('theme_mode', 10)->default('dark');
                $table->boolean('is_default')->default(0);
                $table->dateTime('created_at');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_player_socials')) {
            Capsule::schema()->create('mod_centralvix_player_socials', function ($table) {
                $table->increments('id');
                $table->integer('player_id')->unsigned();
                $table->string('network', 30);
                $table->string('url', 500);
                $table->dateTime('created_at');
                $table->index('player_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_player_settings')) {
            Capsule::schema()->create('mod_centralvix_player_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        CentralVixPlayerThemeService::seedDefaults();
        CentralVixPlayerModule::setDbSetting('track_views', '1');
        return ['status' => 'success', 'description' => 'Módulo CentralVix Player Generator ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => 'Erro na ativação: ' . $e->getMessage()];
    }
}

function centralvix_player_generator_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Tabelas preservadas.'];
}

function centralvix_player_generator_output($vars)
{
    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'players', 'themes', 'settings'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }
    if (!empty($_GET['cvix_player_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_player_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixPlayerModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'settings') {
        if (CentralVixPlayerModule::validateCsrf($_POST['csrf_token'] ?? '')) {
            CentralVixPlayerModule::setDbSetting('track_views', !empty($_POST['track_views']) ? '1' : '0');
            $success = 'Configurações salvas.';
        }
    }
    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixPlayerModule::VERSION);

    CentralVixPlayerModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'csrf' => CentralVixPlayerModule::csrfToken('admin'),
        'success' => $success ?? '',
    ]);
}

function centralvix_player_generator_clientarea($vars)
{
    if (!empty($_GET['cvix_player_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_player_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixPlayerModule::json(['success' => false, 'message' => 'Endpoint não encontrado'], 404);
    }
    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'players');
    if (!in_array($action, ['players', 'builder'], true)) {
        $action = 'players';
    }
    $clientId = (int) $_SESSION['uid'];
    $playerId = (int) ($_GET['id'] ?? 0);
    ob_start();
    CentralVixPlayerModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'action' => $action,
        'clientId' => $clientId,
        'playerId' => $playerId,
        'csrf' => CentralVixPlayerModule::csrfToken('client'),
    ]);
    $html = ob_get_clean();
    return [
        'pagetitle' => CentralVixPlayerModule::lang('client_title', 'Player Generator'),
        'breadcrumb' => [
            'index.php?m=centralvix_player_generator' => CentralVixPlayerModule::lang('client_title', 'Player Generator'),
        ],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => [
            'content' => $html,
            'modulelink' => $vars['modulelink'],
        ],
    ];
}
