<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Catálogo público CentralVix — lê produtos REAIS do WHMCS.
 * Não duplica preços. Não inventa PID.
 */
function centralvix_catalog_pages(): array
{
    return [
        'catalogo' => [
            'title' => 'Catálogo',
            'headline' => 'Serviços CentralVix',
            'lead' => 'Hospedagem, VPS, streaming, sites e sistemas com preços reais do WHMCS.',
            'segments' => [],
        ],
        'hospedagem' => [
            'title' => 'Hospedagem de Sites',
            'headline' => 'Escolha o plano ideal para você',
            'lead' => 'Hospedagem profissional com recursos premium, segurança reforçada e performance máxima para o seu negócio.',
            'segments' => ['hospedagem'],
        ],
        'revenda' => [
            'title' => 'Revenda WHM/cPanel',
            'headline' => 'Escolha o plano ideal para sua Revenda',
            'lead' => 'Tenha sua própria empresa de hospedagem com suporte humanizado e infraestrutura profissional.',
            'segments' => ['revenda', 'revenda-whm-cpanel'],
        ],
        'revenda-hospedagem' => [
            'title' => 'Revenda WHM/cPanel',
            'headline' => 'Escolha o plano ideal para sua Revenda',
            'lead' => 'Tenha sua própria empresa de hospedagem com suporte humanizado e infraestrutura profissional.',
            'segments' => ['revenda', 'revenda-whm-cpanel'],
        ],
        'vps' => [
            'title' => 'VPS',
            'headline' => 'Escolha a configuração ideal para seu VPS',
            'lead' => 'Servidores no Brasil e no Canadá, com IP dedicado e contratação pelo PID real do WHMCS.',
            'segments' => ['vps-brasil', 'vps-canada'],
            'locations' => true,
        ],
        'vps-brasil' => [
            'title' => 'VPS Brasil',
            'headline' => 'Servidores no Brasil com ping baixo',
            'lead' => 'Baixa latência para o público brasileiro. Transferência ilimitada, 1 IP dedicado e migração grátis.',
            'segments' => ['vps-brasil'],
        ],
        'vps-canada' => [
            'title' => 'VPS Canadá',
            'headline' => 'Infraestrutura internacional',
            'lead' => 'Servidores no Canadá com proteção Anti-DDoS e 1 IP dedicado.',
            'segments' => ['vps-canada'],
        ],
        'streaming' => [
            'title' => 'Streaming',
            'headline' => 'Streaming de áudio profissional',
            'lead' => 'Estabilidade para a sua rádio alcançar ouvintes.',
            'segments' => ['streaming'],
        ],
        'revenda-streaming' => [
            'title' => 'Revenda de Streaming',
            'headline' => 'Revenda de streaming de áudio',
            'lead' => 'Pacotes de canais para você revender.',
            'segments' => ['revenda-streaming'],
        ],
        'web-radio' => [
            'title' => 'Web Rádio',
            'headline' => 'Web rádio completa',
            'lead' => 'Streaming, player, site e painel no mesmo pacote.',
            'segments' => ['web-radio'],
        ],
        'aplicativos' => [
            'title' => 'Aplicativos',
            'headline' => 'Aplicativos para rádio, Android e iOS',
            'lead' => 'Do app da rádio ao aplicativo sob medida.',
            'segments' => ['app-radio', 'app-android', 'app-ios', 'dev-app'],
        ],
        'sites' => [
            'title' => 'Sites',
            'headline' => 'Sites, landing pages e lojas',
            'lead' => 'Presença digital com identidade CentralVix.',
            'segments' => ['site', 'landing', 'loja'],
        ],
        'desenvolvimento' => [
            'title' => 'Desenvolvimento',
            'headline' => 'Sistemas, automação e projetos',
            'lead' => 'Soluções web sob medida para o seu negócio.',
            'segments' => ['sistema', 'automacao', 'manutencao', 'suporte', 'licenca'],
        ],
    ];
}

function centralvix_catalog_cache_file(): string
{
    return rtrim(ROOTDIR, '/\\') . DIRECTORY_SEPARATOR . 'templates_c' . DIRECTORY_SEPARATOR . 'cvx_public_catalog.json';
}

function centralvix_catalog_bust_cache(): void
{
    $file = centralvix_catalog_cache_file();
    if (is_file($file)) {
        @unlink($file);
    }
}

function centralvix_catalog_money($value): ?string
{
    if ($value === null || $value === '' || (float) $value <= 0) {
        return null;
    }
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function centralvix_catalog_load(?string $pageSlug = null): array
{
    $pages = centralvix_catalog_pages();
    $page = $pages[$pageSlug] ?? $pages['catalogo'];
    $wanted = $page['segments'] ?? [];

    $all = centralvix_catalog_fetch();
    $products = $all;
    if ($wanted) {
        $products = array_values(array_filter($all, static function (array $row) use ($wanted) {
            return in_array((string) ($row['segment'] ?? ''), $wanted, true)
                || in_array((string) ($row['group_segment'] ?? ''), $wanted, true)
                || in_array((string) ($row['group_slug'] ?? ''), $wanted, true)
                || in_array((string) ($row['location'] ?? ''), $wanted, true);
        }));
    }

    $groups = [];
    foreach ($products as $row) {
        $gid = (int) $row['group_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = [
                'id' => $gid,
                'name' => $row['group_name'],
                'slug' => $row['group_slug'] ?? '',
                'icon' => $row['group_icon'] ?? '',
                'headline' => $row['headline'] ?? '',
                'tagline' => $row['tagline'] ?? '',
                'products' => [],
            ];
        }
        $groups[$gid]['products'][] = $row;
    }

    return [
        'page' => $page,
        'slug' => $pageSlug ?: 'catalogo',
        'groups' => array_values($groups),
        'products' => $products,
        'nav' => $pages,
        'total' => count($all),
    ];
}

function centralvix_catalog_fetch(): array
{
    $cache = centralvix_catalog_cache_file();
    if (is_file($cache) && (time() - filemtime($cache)) < 120) {
        $json = json_decode((string) file_get_contents($cache), true);
        if (is_array($json)) {
            return $json;
        }
    }

    $rows = [];
    try {
        if (class_exists('\\Cvx\\Catalog\\CatalogRepo')) {
            $rows = \Cvx\Catalog\CatalogRepo::publicCatalog();
        } else {
            $addon = ROOTDIR . '/modules/addons/cvxcatalog/lib/CatalogRepo.php';
            if (is_file($addon)) {
                require_once ROOTDIR . '/modules/addons/cvxcatalog/lib/Schema.php';
                if (class_exists('\\Cvx\\Catalog\\Schema')) {
                    \Cvx\Catalog\Schema::install();
                }
                require_once $addon;
                if (class_exists('\\Cvx\\Catalog\\CatalogRepo')) {
                    $rows = \Cvx\Catalog\CatalogRepo::publicCatalog();
                }
            }
        }
    } catch (Throwable $e) {
        $rows = [];
    }

    if (!$rows) {
        $rows = centralvix_catalog_fetch_fallback();
    }

    $dir = dirname($cache);
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($cache, json_encode($rows, JSON_UNESCAPED_UNICODE));
    }
    return $rows;
}

function centralvix_catalog_fetch_fallback(): array
{
    if (!class_exists('\\WHMCS\\Database\\Capsule')) {
        return [];
    }
    try {
        $cid = 1;
        $cur = \WHMCS\Database\Capsule::table('tblcurrencies')->where('default', 1)->first();
        if ($cur) {
            $cid = (int) $cur->id;
        }
        $q = \WHMCS\Database\Capsule::table('tblproducts as p')
            ->join('tblproductgroups as g', 'g.id', '=', 'p.gid')
            ->leftJoin('tblpricing as pr', function ($j) use ($cid) {
                $j->on('pr.relid', '=', 'p.id')->where('pr.type', '=', 'product')->where('pr.currency', '=', $cid);
            })
            ->where('p.hidden', 0)
            ->where('p.retired', 0)
            ->where('g.hidden', 0)
            ->select(
                'p.id', 'p.name', 'p.description', 'p.type', 'p.paytype',
                'g.id as group_id', 'g.name as group_name',
                'pr.monthly', 'pr.annually', 'pr.quarterly'
            )
            ->orderBy('p.gid')
            ->orderBy('p.id');
        $out = [];
        foreach ($q->get() as $r) {
            $out[] = [
                'id' => (int) $r->id,
                'name' => (string) $r->name,
                'description' => (string) ($r->description ?? ''),
                'type' => (string) $r->type,
                'paytype' => (string) $r->paytype,
                'group_id' => (int) $r->group_id,
                'group_name' => (string) $r->group_name,
                'monthly' => $r->monthly,
                'annually' => $r->annually,
                'quarterly' => $r->quarterly,
                'slogan' => '',
                'badge' => '',
                'features' => [],
                'order_url' => '/cart.php?a=add&pid=' . (int) $r->id,
                'segment' => '',
                'group_slug' => '',
                'group_segment' => '',
            ];
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}
