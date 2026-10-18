<?php

namespace Cvx\Catalog;

use WHMCS\Database\Capsule;

final class CatalogInstaller
{
    public static function audit(): array
    {
        $version = defined('WHMCS_VERSION') ? WHMCS_VERSION : 'WHMCS';
        $currency = Capsule::table('tblcurrencies')->where('default', 1)->first();
        $modules = CatalogRepo::modules();
        return [
            'whmcs_version' => $version,
            'currency' => $currency ? (array) $currency : ['code' => 'BRL'],
            'groups' => CatalogRepo::groups(),
            'products' => CatalogRepo::products(),
            'emails' => CatalogRepo::emails(),
            'modules' => $modules,
            'servers' => CatalogRepo::servers(),
            'packages' => CatalogRepo::knownPackages(),
            'has_cpanel' => in_array('cpanel', $modules, true),
            'official_count' => OfficialCatalog::count(),
        ];
    }

    public static function preview(array $slugs = []): array
    {
        $wanted = self::wanted($slugs);
        $rows = [];
        $new = 0;
        $exists = 0;
        foreach ($wanted as $plan) {
            $found = self::findExisting($plan);
            $status = $found ? 'exists' : 'new';
            $status === 'exists' ? $exists++ : $new++;
            $rows[] = [
                'slug' => $plan['slug'],
                'name' => $plan['name'],
                'group' => OfficialCatalog::groups()[$plan['group_slug']]['name'] ?? $plan['group_slug'],
                'monthly' => $plan['monthly'],
                'annually' => $plan['annually'],
                'status' => $status,
                'pid' => $found['id'] ?? null,
                'message' => $found ? ($plan['name'] . ' — já existe — ignorado') : 'Será criado',
            ];
        }
        return [
            'selected' => count($wanted),
            'new' => $new,
            'exists' => $exists,
            'errors' => 0,
            'rows' => $rows,
        ];
    }

    public static function install(array $slugs = [], string $onExists = 'ignore'): array
    {
        $wanted = self::wanted($slugs);
        $batchId = 'CATALOGO-CENTRALVIX-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $backup = self::backup($batchId);
        EmailSeed::ensure();

        $groupsFound = 0;
        $groupsCreated = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        $rows = [];

        $groupMap = [];
        foreach (OfficialCatalog::groups() as $slug => $group) {
            $res = CatalogRepo::findOrCreateGroup([
                'name' => $group['name'],
                'slug' => $group['slug'],
                'segment' => $group['segment'],
                'icon' => $group['icon'],
                'title' => $group['title'],
                'subtitle' => $group['subtitle'],
                'headline' => $group['title'],
                'tagline' => $group['subtitle'],
                'order' => $group['order'],
                'features' => [],
            ]);
            $groupMap[$slug] = $res;
            if (!empty($res['created'])) {
                $groupsCreated++;
            } else {
                $groupsFound++;
            }
        }

        $modules = CatalogRepo::modules();
        $hasCpanel = in_array('cpanel', $modules, true);

        foreach ($wanted as $plan) {
            $group = OfficialCatalog::groups()[$plan['group_slug']] ?? null;
            if (!$group || empty($groupMap[$plan['group_slug']]['id'])) {
                $errors++;
                $rows[] = ['name' => $plan['name'], 'status' => 'error', 'message' => 'Grupo não encontrado'];
                continue;
            }
            $gid = (int) $groupMap[$plan['group_slug']]['id'];
            $emailName = $group['email'];
            $emailId = (int) Capsule::table('tblemailtemplates')->where('name', $emailName)->value('id');
            $module = '';
            if ($hasCpanel && in_array($group['type'], ['hostingaccount', 'reselleraccount'], true)) {
                $module = 'cpanel';
            }

            $existing = self::findExisting($plan);
            if ($existing && $onExists === 'ignore') {
                $skipped++;
                $rows[] = [
                    'name' => $plan['name'],
                    'slug' => $plan['slug'],
                    'pid' => $existing['id'],
                    'status' => 'skipped',
                    'message' => $plan['name'] . ' — já existe — ignorado',
                ];
                continue;
            }

            $payload = [
                'name' => $plan['name'],
                'gid' => $gid,
                'type' => $group['type'],
                'paytype' => 'recurring',
                'monthly' => $plan['monthly'],
                'annually' => $plan['annually'] ?? '',
                'description' => TextFactory::long($plan['name'], $plan['features'], $group['segment']),
                'short_description' => $plan['short'],
                'slogan' => $plan['short'],
                'slug' => $plan['slug'],
                'segment' => $group['segment'],
                'module' => $module,
                'package' => '',
                'autosetup' => 'payment',
                'welcomeemail' => $emailId,
                'welcomeemail_name' => $emailName,
                'showdomainoptions' => $group['type'] === 'other' ? 0 : 1,
                'hidden' => 0,
                'badge' => $plan['badge'],
                'is_featured' => $plan['featured'],
                'compare_monthly' => $plan['compare_monthly'] ?? '',
                'discount_percentage' => $plan['discount_percentage'] ?? 0,
                'promotion_label' => !empty($plan['discount_percentage']) ? ($plan['discount_percentage'] . '% OFF') : '',
                'location' => $plan['location'] ?? ($group['location'] ?? ''),
                'location_label' => $plan['location_label'] ?? ($group['location_label'] ?? ''),
                'sort_order' => $plan['sort_order'],
                'features' => $plan['features'],
                'pub_status' => 'published',
            ];

            try {
                $res = CatalogRepo::createProduct($payload, $onExists);
                $status = !empty($res['skipped']) ? 'skipped' : (!empty($res['updated']) ? 'updated' : 'created');
                if ($status === 'created') {
                    $created++;
                } elseif ($status === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
                CatalogRepo::log('catalog.install', [
                    'batch_id' => $batchId,
                    'product_id' => $res['id'],
                    'product_name' => $res['name'],
                    'message' => $status,
                ]);
                $rows[] = [
                    'name' => $plan['name'],
                    'slug' => $plan['slug'],
                    'pid' => $res['id'],
                    'status' => $status,
                    'message' => $status === 'skipped' ? ($plan['name'] . ' — já existe — ignorado') : ('PID ' . $res['id']),
                ];
            } catch (\Throwable $e) {
                $errors++;
                $rows[] = ['name' => $plan['name'], 'slug' => $plan['slug'], 'status' => 'error', 'message' => $e->getMessage()];
            }
        }

        CatalogRepo::bustPublicCache();
        CatalogRepo::log('catalog.install.batch', [
            'batch_id' => $batchId,
            'message' => "Criados: {$created} · Atualizados: {$updated} · Ignorados: {$skipped} · Erros: {$errors}",
            'after' => compact('created', 'updated', 'skipped', 'errors'),
        ]);

        return [
            'ok' => $errors === 0,
            'batch_id' => $batchId,
            'backup' => $backup,
            'groups_found' => $groupsFound,
            'groups_created' => $groupsCreated,
            'analyzed' => count($wanted),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'rows' => $rows,
        ];
    }

    public static function backup(string $batchId): string
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . $batchId . '.json';
        $payload = [
            'batch_id' => $batchId,
            'created_at' => date('c'),
            'groups' => CatalogRepo::groups(),
            'products' => CatalogRepo::products(),
        ];
        @file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        return is_file($file) ? $file : '';
    }

    private static function wanted(array $slugs): array
    {
        $all = OfficialCatalog::products();
        if (!$slugs) {
            return $all;
        }
        $set = array_flip($slugs);
        return array_values(array_filter($all, static fn(array $p) => isset($set[$p['slug']])));
    }

    private static function findExisting(array $plan): ?array
    {
        $meta = Capsule::table('mod_cvxcatalog_product_meta')->where('slug', $plan['slug'])->first();
        if ($meta) {
            $p = Capsule::table('tblproducts')->where('id', $meta->product_id)->first();
            if ($p) {
                return ['id' => (int) $p->id, 'name' => $p->name];
            }
        }
        $group = OfficialCatalog::groups()[$plan['group_slug']] ?? null;
        if ($group) {
            $gid = Capsule::table('tblproductgroups')->where('name', $group['name'])->value('id');
            if ($gid) {
                $p = Capsule::table('tblproducts')->where('gid', $gid)->where('name', $plan['name'])->first();
                if ($p) {
                    return ['id' => (int) $p->id, 'name' => $p->name];
                }
            }
        }
        $p = Capsule::table('tblproducts')->where('name', $plan['name'])->first();
        return $p ? ['id' => (int) $p->id, 'name' => $p->name] : null;
    }
}
