<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use Cvx\Catalog\Access;
use Cvx\Catalog\CatalogInstaller;
use Cvx\Catalog\CatalogRepo;
use Cvx\Catalog\EmailSeed;
use Cvx\Catalog\OfficialCatalog;
use Cvx\Catalog\Schema;
use Cvx\Catalog\Segments;
use Cvx\Catalog\TextFactory;
use WHMCS\Database\Capsule;

spl_autoload_register(static function (string $class): void {
    if (strpos($class, 'Cvx\\Catalog\\') !== 0) {
        return;
    }
    $file = __DIR__ . '/lib/' . substr($class, strlen('Cvx\\Catalog\\')) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

function cvxcatalog_config()
{
    return [
        'name' => 'CentralVix — Gerador de Catálogo',
        'description' => 'Cria e mantém produtos reais do WHMCS em massa, com publicação no site CentralVix.',
        'version' => '1.2.2',
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [],
    ];
}

function cvxcatalog_activate()
{
    try {
        Schema::install();
        EmailSeed::ensure();
        return ['status' => 'success', 'description' => 'Gerador de Catálogo ativado.'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function cvxcatalog_deactivate()
{
    return ['status' => 'success', 'description' => 'Módulo desativado. Os produtos do WHMCS foram preservados.'];
}

function cvxcatalog_upgrade($vars)
{
    try {
        Schema::install();
        EmailSeed::ensure();
        return ['status' => 'success', 'description' => 'Gerador de Catálogo atualizado para ' . ($vars['version'] ?? '1.0.0')];
    } catch (Throwable $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function cvxcatalog_output($vars)
{
    Schema::install();
    $page = preg_replace('/[^a-z]/', '', (string) ($_GET['cvx'] ?? 'dashboard')) ?: 'dashboard';
    $aliases = ['produtos' => 'catalog', 'modelos' => 'templates', 'sincronizacao' => 'sync', 'configuracoes' => 'settings', 'instalar' => 'install'];
    $page = $aliases[$page] ?? $page;

    if ($page === 'ajax') {
        cvxcatalog_ajax();
        return;
    }

    $installPreview = null;
    $installResult = null;
    $wizardResult = null;
    $flashError = '';
    $flashOk = '';
    $do = (string) ($_POST['cvx_do'] ?? $_GET['cvx_act'] ?? '');
    if (in_array($do, ['run', 'bulk'], true) && ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' && empty($_GET['confirm'])) {
        $do = '';
    }
    if ($do !== '') {
        try {
            if ($do === 'preview') {
                Access::require(Access::BULK);
                $slugs = $_POST['slugs'] ?? [];
                $installPreview = CatalogInstaller::preview(is_array($slugs) ? $slugs : []);
                $page = 'install';
                $flashOk = 'Preview pronto: ' . (int) $installPreview['new'] . ' novos, ' . (int) $installPreview['exists'] . ' já existentes.';
            } elseif ($do === 'run') {
                Access::require(Access::BULK);
                $slugs = $_POST['slugs'] ?? [];
                $installResult = CatalogInstaller::install(is_array($slugs) ? $slugs : [], 'ignore');
                $page = 'install';
                $flashOk = 'WHMCS atualizado. Criados: ' . (int) $installResult['created'] . ' · Atualizados: ' . (int) $installResult['updated'] . ' · Já existiam: ' . (int) $installResult['skipped'] . ' · Erros: ' . (int) $installResult['errors'];
            } elseif ($do === 'bulk') {
                Access::require(Access::BULK);
                $payload = $_POST;
                if (empty($payload['segment']) && !empty($_GET['seg'])) {
                    $payload['segment'] = (string) $_GET['seg'];
                }
                if (empty($payload['plans'])) {
                    $seg = (string) ($payload['segment'] ?? '');
                    $preset = \Cvx\Catalog\CatalogPresets::for($seg);
                    $model = Segments::all()[$seg] ?? null;
                    if ($preset && $model) {
                        $plans = [];
                        foreach ($preset as $wp) {
                            $plans[] = [
                                'name' => $wp['name'],
                                'monthly' => $wp['monthly'] ?? '',
                                'annually' => $wp['annually'] ?? '',
                                'quarterly' => $wp['quarterly'] ?? '',
                                'featured' => $wp['featured'] ?? 0,
                                'feature_values' => $wp['features'] ?? [],
                            ];
                        }
                        $payload['group_name'] = $payload['group_name'] ?? $model['name'];
                        $payload['type'] = $payload['type'] ?? $model['type'];
                        $payload['module'] = $payload['module'] ?? $model['module'];
                        $payload['welcomeemail_name'] = $payload['welcomeemail_name'] ?? $model['email'];
                        $payload['autosetup'] = $payload['autosetup'] ?? 'payment';
                        $payload['on_exists'] = $payload['on_exists'] ?? 'ignore';
                        $payload['plans'] = json_encode($plans, JSON_UNESCAPED_UNICODE);
                    }
                }
                $wizardResult = cvxcatalog_bulk_create($payload);
                $page = 'wizard';
                $flashOk = 'Lote ' . ($wizardResult['batch_id'] ?? '') . ': ' . count($wizardResult['created'] ?? []) . ' criados, ' . count($wizardResult['skipped'] ?? []) . ' ignorados, ' . count($wizardResult['errors'] ?? []) . ' erros.';
            } elseif ($do === 'save_product') {
                $id = (int) ($_POST['id'] ?? 0);
                Access::require($id ? Access::EDIT : Access::CREATE);
                $data = $_POST;
                if (!empty($_POST['features_json'])) {
                    $data['features'] = json_decode((string) $_POST['features_json'], true) ?: [];
                }
                if (!empty($_POST['customfields_json'])) {
                    $data['customfields'] = json_decode((string) $_POST['customfields_json'], true) ?: [];
                }
                $res = $id ? CatalogRepo::updateProduct($id, $data) : CatalogRepo::createProduct($data, 'update');
                CatalogRepo::log($id ? 'product.update' : 'product.create', ['product_id' => $res['id'], 'product_name' => $res['name']]);
                $flashOk = 'Produto salvo no WHMCS (PID ' . (int) $res['id'] . ').';
                $page = 'catalog';
            }
        } catch (Throwable $e) {
            $flashError = $e->getMessage();
        }
    }

    $base = 'addonmodules.php?module=cvxcatalog';
    $stats = CatalogRepo::stats();
    $groups = CatalogRepo::groups();
    $products = CatalogRepo::products([
        'q' => $_GET['q'] ?? '',
        'gid' => $_GET['gid'] ?? '',
        'hidden' => $_GET['hidden'] ?? '',
        'type' => $_GET['type'] ?? '',
    ]);
    $emails = CatalogRepo::emails();
    $servers = CatalogRepo::servers();
    $serverGroups = CatalogRepo::serverGroups();
    $modules = CatalogRepo::modules();
    $segments = Segments::all();
    $packages = CatalogRepo::knownPackages();
    $officialPlans = OfficialCatalog::products();
    $officialGroups = OfficialCatalog::groups();
    $wizardSeg = preg_replace('/[^a-z0-9\-]/', '', (string) ($_GET['seg'] ?? $_POST['segment'] ?? ''));
    $wizardModel = ($wizardSeg && isset($segments[$wizardSeg])) ? $segments[$wizardSeg] : null;
    $edit = !empty($_GET['pid']) ? CatalogRepo::product((int) $_GET['pid']) : null;
    $logs = Capsule::table('mod_cvxcatalog_logs')->orderBy('id', 'desc')->limit(80)->get();
    $roles = Capsule::table('tbladminroles')->orderBy('name')->get(['id', 'name']);
    $acl = [];
    if (Capsule::schema()->hasTable('mod_cvxcatalog_acl')) {
        foreach (Capsule::table('mod_cvxcatalog_acl')->get() as $row) {
            $acl[$row->role_id][$row->permission] = (int) $row->allowed;
        }
    }
    $perms = Access::all();
    $can = [];
    foreach (array_keys($perms) as $key) {
        $can[$key] = Access::can($key);
    }
    $token = function_exists('generate_token') ? generate_token('plain') : '';
    $whmcsVersion = defined('WHMCS_VERSION') ? WHMCS_VERSION : (class_exists('\\WHMCS\\Application') ? '9.x' : 'WHMCS');

    include __DIR__ . '/views/app.php';
}

function cvxcatalog_ajax(): void
{
    header('Content-Type: application/json; charset=utf-8');
    $action = (string) ($_POST['action'] ?? $_GET['action'] ?? '');
    try {
        switch ($action) {
            case 'generate_text':
                $name = (string) ($_POST['name'] ?? 'Plano');
                $seg = (string) ($_POST['segment'] ?? '');
                $feats = json_decode((string) ($_POST['features'] ?? '[]'), true) ?: [];
                echo json_encode([
                    'ok' => true,
                    'short' => TextFactory::short($name, $seg),
                    'long' => TextFactory::long($name, $feats, $seg),
                ]);
                return;
            case 'install_audit':
                Access::require(Access::BULK);
                echo json_encode(['ok' => true] + CatalogInstaller::audit());
                return;
            case 'install_preview':
                Access::require(Access::BULK);
                $slugs = json_decode((string) ($_POST['slugs'] ?? $_GET['slugs'] ?? '[]'), true) ?: [];
                if (!$slugs && !empty($_GET['slugs'])) {
                    $slugs = array_filter(explode(',', (string) $_GET['slugs']));
                }
                echo json_encode(['ok' => true] + CatalogInstaller::preview($slugs));
                return;
            case 'install_run':
                Access::require(Access::BULK);
                $slugs = json_decode((string) ($_POST['slugs'] ?? '[]'), true) ?: [];
                $onExists = (string) ($_POST['on_exists'] ?? 'ignore');
                echo json_encode(['ok' => true] + CatalogInstaller::install($slugs, $onExists));
                return;
            case 'bulk_create':
                Access::require(Access::BULK);
                echo json_encode(['ok' => true] + cvxcatalog_bulk_create($_POST));
                return;
            case 'save_product':
                $id = (int) ($_POST['id'] ?? 0);
                Access::require($id ? Access::EDIT : Access::CREATE);
                $data = $_POST;
                if (!empty($_POST['features_json'])) {
                    $data['features'] = json_decode((string) $_POST['features_json'], true) ?: [];
                }
                if (!empty($_POST['customfields_json'])) {
                    $data['customfields'] = json_decode((string) $_POST['customfields_json'], true) ?: [];
                }
                $res = $id ? CatalogRepo::updateProduct($id, $data) : CatalogRepo::createProduct($data, 'update');
                CatalogRepo::log($id ? 'product.update' : 'product.create', ['product_id' => $res['id'], 'product_name' => $res['name']]);
                echo json_encode(['ok' => true, 'product' => $res]);
                return;
            case 'duplicate':
                Access::require(Access::CREATE);
                $res = CatalogRepo::duplicateProduct((int) $_POST['id'], $_POST['name'] ?? null);
                CatalogRepo::log('product.duplicate', ['product_id' => $res['id'], 'product_name' => $res['name']]);
                echo json_encode(['ok' => true, 'product' => $res]);
                return;
            case 'duplicate_group':
                Access::require(Access::CREATE);
                $res = CatalogRepo::duplicateGroup((int) $_POST['id'], (string) ($_POST['name'] ?? ''));
                CatalogRepo::log('group.duplicate', ['product_name' => $res['group']['name'] ?? '']);
                echo json_encode(['ok' => true] + $res);
                return;
            case 'hide':
                Access::require(Access::EDIT);
                CatalogRepo::hideOrRetire((int) $_POST['id'], (string) ($_POST['mode'] ?? 'hide'));
                echo json_encode(['ok' => true]);
                return;
            case 'delete':
                Access::require(Access::DELETE);
                CatalogRepo::deleteProduct((int) $_POST['id']);
                CatalogRepo::log('product.delete', ['product_id' => (int) $_POST['id']]);
                echo json_encode(['ok' => true]);
                return;
            case 'bust_cache':
                CatalogRepo::bustPublicCache();
                echo json_encode(['ok' => true, 'message' => 'Cache do site invalidado.']);
                return;
            case 'save_acl':
                Access::require(Access::EDIT);
                $role = (int) ($_POST['role_id'] ?? 0);
                $perm = (string) ($_POST['permission'] ?? '');
                $allowed = (int) ($_POST['allowed'] ?? 1);
                if ($role && $perm) {
                    Capsule::table('mod_cvxcatalog_acl')->updateOrInsert(
                        ['role_id' => $role, 'permission' => $perm],
                        ['allowed' => $allowed]
                    );
                }
                echo json_encode(['ok' => true]);
                return;
            case 'save_group':
                $g = CatalogRepo::findOrCreateGroup($_POST);
                echo json_encode(['ok' => true, 'group' => $g]);
                return;
            case 'adjust_prices':
                Access::require(Access::PRICES);
                $ids = array_filter(array_map('intval', explode(',', (string) ($_POST['ids'] ?? ''))));
                $rows = CatalogRepo::adjustPrices($ids, (float) ($_POST['percent'] ?? 0), (string) ($_POST['cycles'] ?? 'all'));
                CatalogRepo::log('prices.adjust', ['after' => $rows, 'message' => ($_POST['percent'] ?? '') . '%']);
                echo json_encode(['ok' => true, 'rows' => $rows]);
                return;
            case 'csv_template':
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=modelo-catalogo-centralvix.csv');
                $out = fopen('php://output', 'w');
                fputcsv($out, ['grupo', 'produto', 'slug', 'tipo', 'mensal', 'trimestral', 'semestral', 'anual', 'bienal', 'trienal', 'descricao_curta', 'descricao', 'email', 'modulo', 'package', 'destaque', 'oculto']);
                fputcsv($out, ['Hospedagem', 'Start', 'start', 'hostingaccount', '', '', '', '', '', '', '', '', 'CentralVix — Hospedagem Ativada', 'cpanel', '', '0', '0']);
                fclose($out);
                return;
            case 'export':
                $fmt = (string) ($_GET['format'] ?? 'json');
                $rows = CatalogRepo::products();
                if ($fmt === 'csv') {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename=catalogo-centralvix.csv');
                    $out = fopen('php://output', 'w');
                    fputcsv($out, ['id', 'grupo', 'produto', 'tipo', 'mensal', 'anual', 'modulo', 'oculto']);
                    foreach ($rows as $r) {
                        fputcsv($out, [$r['id'], $r['group_name'], $r['name'], $r['type'], $r['monthly'], $r['annually'], $r['servertype'], $r['hidden']]);
                    }
                    fclose($out);
                    return;
                }
                echo json_encode(['ok' => true, 'products' => $rows, 'groups' => CatalogRepo::groups()], JSON_UNESCAPED_UNICODE);
                return;
            case 'import':
                $payload = json_decode((string) ($_POST['json'] ?? ''), true);
                if (!is_array($payload)) {
                    throw new InvalidArgumentException('JSON inválido.');
                }
                $batch = cvxcatalog_import($payload, (string) ($_POST['on_exists'] ?? 'ignore'));
                echo json_encode(['ok' => true] + $batch);
                return;
            default:
                echo json_encode(['ok' => false, 'message' => 'Ação inválida']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

function cvxcatalog_bulk_create(array $post): array
{
    $segment = (string) ($post['segment'] ?? '');
    $model = Segments::all()[$segment] ?? null;
    if (!$model) {
        throw new InvalidArgumentException('Segmento inválido.');
    }
    $onExists = (string) ($post['on_exists'] ?? 'ignore');
    $plans = json_decode((string) ($post['plans'] ?? '[]'), true);
    if (!$plans) {
        throw new InvalidArgumentException('Informe os planos e preços.');
    }
    foreach ($plans as $plan) {
        if (empty($plan['name'])) {
            throw new InvalidArgumentException('Todo plano precisa de nome.');
        }
        if ((float) ($plan['monthly'] ?? 0) <= 0 && (float) ($plan['annually'] ?? 0) <= 0) {
            throw new InvalidArgumentException('Preencha o preço mensal ou anual de "' . $plan['name'] . '". Não publicamos preço inventado.');
        }
    }

    $batchId = 'CAT-' . date('Ymd-His');
    $group = CatalogRepo::findOrCreateGroup([
        'name' => $post['group_name'] ?? $model['name'],
        'slug' => $model['slug'],
        'segment' => $segment,
        'icon' => $model['icon'],
        'title' => $post['group_title'] ?? $model['headline'],
        'subtitle' => $post['group_subtitle'] ?? $model['tagline'],
        'headline' => $post['group_title'] ?? $model['headline'],
        'tagline' => $post['group_subtitle'] ?? $model['tagline'],
        'features' => $model['features'],
    ]);

    $emailName = (string) ($post['welcomeemail_name'] ?: EmailSeed::mapForSegment($segment));
    EmailSeed::ensure();
    $emailId = (int) Capsule::table('tblemailtemplates')->where('name', $emailName)->value('id');

    $created = [];
    $skipped = [];
    $errors = [];
    foreach ($plans as $i => $plan) {
        $feats = [];
        foreach ($model['features'] as $fname) {
            $feats[] = ['name' => $fname, 'value' => $plan['feature_values'][$fname] ?? ''];
        }
        try {
            $res = CatalogRepo::createProduct([
                'name' => $plan['name'],
                'gid' => $group['id'],
                'type' => $post['type'] ?? $model['type'],
                'paytype' => 'recurring',
                'monthly' => $plan['monthly'] ?? '',
                'annually' => $plan['annually'] ?? '',
                'quarterly' => $plan['quarterly'] ?? '',
                'description' => TextFactory::long($plan['name'], $feats, $segment),
                'short_description' => TextFactory::short($plan['name'], $segment),
                'slogan' => TextFactory::short($plan['name'], $segment),
                'module' => $post['module'] ?? $model['module'],
                'package' => $post['package'] ?? '',
                'servergroup' => $post['servergroup'] ?? 0,
                'autosetup' => $post['autosetup'] ?? 'payment',
                'welcomeemail' => $emailId,
                'welcomeemail_name' => $emailName,
                'showdomainoptions' => $post['showdomainoptions'] ?? ($model['type'] === 'other' ? 0 : 1),
                'hidden' => 0,
                'segment' => $segment,
                'badge' => !empty($plan['featured']) ? 'Mais Popular' : '',
                'is_featured' => !empty($plan['featured']) ? 1 : 0,
                'features' => $feats,
            ], $onExists);
            CatalogRepo::log('bulk.create', ['batch_id' => $batchId, 'product_id' => $res['id'], 'product_name' => $res['name']]);
            if (!empty($res['skipped'])) {
                $skipped[] = $res;
            } else {
                $created[] = $res;
            }
        } catch (Throwable $e) {
            $errors[] = ['name' => $plan['name'], 'error' => $e->getMessage()];
        }
    }

    return [
        'batch_id' => $batchId,
        'group' => $group,
        'created' => $created,
        'skipped' => $skipped,
        'errors' => $errors,
    ];
}

function cvxcatalog_import(array $payload, string $onExists): array
{
    $group = CatalogRepo::findOrCreateGroup($payload['group'] ?? ['name' => 'Importados']);
    $created = [];
    foreach (($payload['products'] ?? []) as $p) {
        $p['gid'] = $group['id'];
        $created[] = CatalogRepo::createProduct($p, $onExists);
    }
    return ['group' => $group, 'created' => $created];
}
