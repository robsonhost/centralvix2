<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

require_once __DIR__ . '/lib/Security.php';
CentralVixSignModule::autoload();
CentralVixSignDocumentService::ensureStorage();

function centralvix_assinatura_digital_config()
{
    return [
        'name' => 'CentralVix Assinatura Digital',
        'description' => 'Fluxo de aceite e assinatura eletrônica de documentos com evidências técnicas e auditoria.',
        'version' => CentralVixSignModule::VERSION,
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'max_upload_mb' => [
                'FriendlyName' => 'Upload PDF máximo (MB)',
                'Type' => 'text',
                'Default' => '15',
            ],
            'default_expiry_days' => [
                'FriendlyName' => 'Expiração padrão (dias)',
                'Type' => 'text',
                'Default' => '30',
            ],
        ],
    ];
}

function centralvix_assinatura_digital_activate()
{
    try {
        if (!Capsule::schema()->hasTable('mod_centralvix_sign_documents')) {
            Capsule::schema()->create('mod_centralvix_sign_documents', function ($table) {
                $table->increments('id');
                $table->integer('client_id')->unsigned();
                $table->integer('template_id')->unsigned()->default(0);
                $table->string('title', 200);
                $table->string('original_filename', 255);
                $table->string('stored_filename', 255);
                $table->string('mime_type', 100)->default('application/pdf');
                $table->unsignedBigInteger('size_bytes')->default(0);
                $table->string('document_hash', 64);
                $table->unsignedInteger('version')->default(1);
                $table->string('status', 30)->default('rascunho');
                $table->string('transaction_id', 64)->unique();
                $table->string('signer_name', 150)->nullable();
                $table->string('signer_document', 50)->nullable();
                $table->string('signer_ip', 45)->nullable();
                $table->string('receipt_filename', 255)->nullable();
                $table->string('signed_copy_filename', 255)->nullable();
                $table->dateTime('signed_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->integer('created_by_admin')->unsigned()->default(0);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
                $table->index('client_id');
                $table->index('status');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_sign_requests')) {
            Capsule::schema()->create('mod_centralvix_sign_requests', function ($table) {
                $table->increments('id');
                $table->integer('document_id')->unsigned();
                $table->integer('client_id')->unsigned();
                $table->string('token_hash', 64);
                $table->string('status', 20)->default('active');
                $table->dateTime('expires_at');
                $table->dateTime('used_at')->nullable();
                $table->dateTime('created_at');
                $table->index(['token_hash', 'status']);
                $table->index('document_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_sign_events')) {
            Capsule::schema()->create('mod_centralvix_sign_events', function ($table) {
                $table->increments('id');
                $table->integer('document_id')->unsigned();
                $table->integer('client_id')->unsigned()->default(0);
                $table->string('event_type', 50);
                $table->text('message')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->text('evidence_json')->nullable();
                $table->dateTime('created_at');
                $table->index('document_id');
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_sign_templates')) {
            Capsule::schema()->create('mod_centralvix_sign_templates', function ($table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('title', 200)->nullable();
                $table->text('description')->nullable();
                $table->boolean('active')->default(1);
                $table->dateTime('created_at');
                $table->dateTime('updated_at')->nullable();
            });
        }
        if (!Capsule::schema()->hasTable('mod_centralvix_sign_settings')) {
            Capsule::schema()->create('mod_centralvix_sign_settings', function ($table) {
                $table->increments('id');
                $table->string('setting_key', 100)->unique();
                $table->text('setting_value')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
        CentralVixSignModule::setDbSetting('email_enabled', '1');
        CentralVixSignModule::setDbSetting('legal_notice', 'Este sistema registra evidências técnicas de aceite eletrônico. Não substitui certificação ICP-Brasil.');
        return ['status' => 'success', 'description' => 'CentralVix Assinatura Digital ativado.'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function centralvix_assinatura_digital_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Dados e arquivos preservados.'];
}

function centralvix_assinatura_digital_output($vars)
{
    CentralVixSignDocumentService::checkExpired();

    if (!empty($_GET['cvix_sign_dl'])) {
        CentralVixSignDocumentService::serveFile((int) $_GET['cvix_sign_dl'], $_GET['file'] ?? 'original', null);
    }
    if (!empty($_GET['cvix_sign_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_sign_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixSignModule::json(['success' => false], 404);
    }

    $tab = preg_replace('/[^a-z_]/', '', $_GET['tab'] ?? 'dashboard');
    $allowed = ['dashboard', 'documents', 'templates', 'settings', 'audit'];
    if (!in_array($tab, $allowed, true)) {
        $tab = 'dashboard';
    }

    $success = $error = $tokenLink = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && CentralVixSignModule::validateCsrf($_POST['csrf_token'] ?? '')) {
        if ($tab === 'documents' && !empty($_POST['upload_document'])) {
            $days = (int) CentralVixSignModule::setting('default_expiry_days', 30);
            $r = CentralVixSignDocumentService::createFromUpload((int) $_POST['client_id'], $_POST['title'] ?? '', (int) ($_POST['template_id'] ?? 0), $days);
            if ($r['success']) {
                $success = 'Documento criado (ID ' . $r['id'] . ').';
            } else {
                $error = $r['message'] ?? 'Erro';
            }
        } elseif ($tab === 'documents' && !empty($_POST['send_document'])) {
            $r = CentralVixSignDocumentService::sendForSignature((int) $_POST['document_id']);
            if ($r['success']) {
                $success = 'Enviado para assinatura.';
                $tokenLink = $r['link'] ?? '';
            } else {
                $error = $r['message'] ?? 'Erro';
            }
        } elseif ($tab === 'documents' && !empty($_POST['cancel_document'])) {
            CentralVixSignDocumentService::cancel((int) $_POST['document_id']);
            $success = 'Documento cancelado.';
        } elseif ($tab === 'templates' && !empty($_POST['save_template'])) {
            CentralVixSignDocumentService::saveTemplate($_POST, (int) ($_POST['template_id'] ?? 0));
            $success = 'Template salvo.';
        } elseif ($tab === 'settings') {
            CentralVixSignModule::setDbSetting('email_enabled', !empty($_POST['email_enabled']) ? '1' : '0');
            CentralVixSignModule::setDbSetting('legal_notice', CentralVixSignSecurity::sanitizeText($_POST['legal_notice'] ?? '', 2000));
            $success = 'Configurações salvas.';
        }
    }

    require_once __DIR__ . '/../centralvix_shared/lib/AdminUi.php';
    CentralVixAdminUi::renderCssLink(CentralVixSignModule::VERSION);

    CentralVixSignModule::renderAdmin($tab, [
        'modulelink' => $vars['modulelink'],
        'tab' => $tab,
        'csrf' => CentralVixSignModule::csrfToken('admin'),
        'success' => $success,
        'error' => $error,
        'tokenLink' => $tokenLink,
    ]);
}

function centralvix_assinatura_digital_clientarea($vars)
{
    CentralVixSignDocumentService::checkExpired();

    if (!empty($_GET['cvix_sign_dl'])) {
        CentralVixSignDocumentService::serveFile((int) $_GET['cvix_sign_dl'], $_GET['file'] ?? 'original', (int) $_SESSION['uid']);
    }
    if (!empty($_GET['cvix_sign_ajax'])) {
        $action = preg_replace('/[^a-z_]/', '', $_GET['cvix_sign_ajax']);
        $f = __DIR__ . '/ajax/' . $action . '.php';
        if (is_file($f)) {
            require $f;
            exit;
        }
        CentralVixSignModule::json(['success' => false], 404);
    }

    $action = preg_replace('/[^a-z_]/', '', $_GET['action'] ?? 'documents');
    if (!in_array($action, ['documents', 'sign'], true)) {
        $action = 'documents';
    }

    ob_start();
    CentralVixSignModule::renderClient($action, [
        'modulelink' => $vars['modulelink'],
        'clientId' => (int) $_SESSION['uid'],
        'documentId' => (int) ($_GET['id'] ?? 0),
        'csrf' => CentralVixSignModule::csrfToken('client'),
    ]);
    $html = ob_get_clean();

    return [
        'pagetitle' => CentralVixSignModule::lang('client_title', 'Assinatura Digital'),
        'breadcrumb' => ['index.php?m=centralvix_assinatura_digital' => CentralVixSignModule::lang('client_title', 'Assinatura Digital')],
        'templatefile' => 'client_wrapper',
        'requirelogin' => true,
        'vars' => ['content' => $html, 'modulelink' => $vars['modulelink']],
    ];
}
