<?php

namespace Cvx\Catalog;

use WHMCS\Database\Capsule;

final class CatalogRepo
{
    public static function currencyId(): int
    {
        $row = Capsule::table('tblcurrencies')->where('default', 1)->first();
        return $row ? (int) $row->id : 1;
    }

    public static function stats(): array
    {
        $cid = self::currencyId();
        $products = Capsule::table('tblproducts');
        $pricing = Capsule::table('tblpricing')->where('type', 'product')->where('currency', $cid);

        $noPrice = Capsule::table('tblproducts as p')
            ->leftJoin('tblpricing as pr', function ($j) use ($cid) {
                $j->on('pr.relid', '=', 'p.id')->where('pr.type', '=', 'product')->where('pr.currency', '=', $cid);
            })
            ->where(function ($q) {
                $q->whereNull('pr.id')
                    ->orWhere(function ($q2) {
                        $q2->where('p.paytype', 'recurring')
                            ->where('pr.monthly', '<=', 0)
                            ->where('pr.annually', '<=', 0);
                    });
            })
            ->count();

        return [
            'products' => (int) $products->count(),
            'groups' => (int) Capsule::table('tblproductgroups')->count(),
            'active' => (int) (clone $products)->where('hidden', 0)->where('retired', 0)->count(),
            'hidden' => (int) (clone $products)->where('hidden', 1)->count(),
            'recurring' => (int) (clone $products)->where('paytype', 'recurring')->count(),
            'no_price' => $noPrice,
            'no_email' => (int) (clone $products)->where(function ($q) {
                $q->whereNull('welcomeemail')->orWhere('welcomeemail', 0)->orWhere('welcomeemail', '');
            })->count(),
            'no_desc' => (int) (clone $products)->where(function ($q) {
                $q->whereNull('description')->orWhere('description', '');
            })->count(),
        ];
    }

    public static function groups(): array
    {
        $groups = Capsule::table('tblproductgroups as g')
            ->leftJoin('mod_cvxcatalog_group_meta as m', 'm.group_id', '=', 'g.id')
            ->select('g.*', 'm.slug', 'm.segment', 'm.icon', 'm.title', 'm.subtitle', 'm.color', 'm.featured', 'm.features_json')
            ->orderBy('g.order')
            ->get();
        $counts = Capsule::table('tblproducts')->select('gid', Capsule::raw('COUNT(*) as total'))->groupBy('gid')->pluck('total', 'gid');
        $out = [];
        foreach ($groups as $g) {
            $row = (array) $g;
            $row['product_count'] = (int) ($counts[$g->id] ?? 0);
            $out[] = $row;
        }
        return $out;
    }

    public static function products(array $filters = []): array
    {
        $cid = self::currencyId();
        $q = Capsule::table('tblproducts as p')
            ->leftJoin('tblproductgroups as g', 'g.id', '=', 'p.gid')
            ->leftJoin('tblpricing as pr', function ($j) use ($cid) {
                $j->on('pr.relid', '=', 'p.id')->where('pr.type', '=', 'product')->where('pr.currency', '=', $cid);
            })
            ->leftJoin('mod_cvxcatalog_product_meta as m', 'm.product_id', '=', 'p.id')
            ->select(
                'p.id', 'p.gid', 'p.name', 'p.type', 'p.paytype', 'p.hidden', 'p.retired',
                'p.servertype', 'p.welcomeemail', 'p.description', 'p.autosetup',
                'g.name as group_name',
                'pr.monthly', 'pr.quarterly', 'pr.semiannually', 'pr.annually', 'pr.biennially', 'pr.triennially',
                'm.slug', 'm.segment', 'm.slogan', 'm.badge', 'm.status as pub_status'
            );

        if (!empty($filters['q'])) {
            $like = '%' . $filters['q'] . '%';
            $q->where(function ($w) use ($like) {
                $w->where('p.name', 'like', $like)
                    ->orWhere('p.id', $filters['q'])
                    ->orWhere('g.name', 'like', $like)
                    ->orWhere('p.servertype', 'like', $like)
                    ->orWhere('m.slug', 'like', $like);
            });
        }
        if (!empty($filters['gid'])) {
            $q->where('p.gid', (int) $filters['gid']);
        }
        if (($filters['hidden'] ?? '') !== '') {
            $q->where('p.hidden', (int) $filters['hidden']);
        }
        if (!empty($filters['type'])) {
            $q->where('p.type', $filters['type']);
        }

        return $q->orderBy('p.gid')->orderBy('p.id')->limit(400)->get()->map(fn ($r) => (array) $r)->all();
    }

    public static function product(int $id): ?array
    {
        $cid = self::currencyId();
        $p = Capsule::table('tblproducts as p')
            ->leftJoin('tblpricing as pr', function ($j) use ($cid) {
                $j->on('pr.relid', '=', 'p.id')->where('pr.type', '=', 'product')->where('pr.currency', '=', $cid);
            })
            ->leftJoin('mod_cvxcatalog_product_meta as m', 'm.product_id', '=', 'p.id')
            ->where('p.id', $id)
            ->select('p.*', 'pr.monthly', 'pr.quarterly', 'pr.semiannually', 'pr.annually', 'pr.biennially', 'pr.triennially', 'pr.msetupfee', 'm.slug', 'm.segment', 'm.slogan', 'm.badge', 'm.icon', 'm.image', 'm.color', 'm.seo_title', 'm.seo_description', 'm.compare_monthly', 'm.compare_annually', 'm.status as pub_status')
            ->first();
        if (!$p) {
            return null;
        }
        $row = (array) $p;
        $row['features'] = Capsule::table('mod_cvxcatalog_features')
            ->where('product_id', $id)->orderBy('sort_order')->get()->map(fn ($f) => (array) $f)->all();
        $row['customfields'] = Capsule::table('tblcustomfields')
            ->where('type', 'product')->where('relid', $id)->orderBy('sortorder')->get()->map(fn ($f) => (array) $f)->all();
        $row['active_services'] = (int) Capsule::table('tblhosting')->where('packageid', $id)->whereIn('domainstatus', ['Active', 'Suspended', 'Pending'])->count();
        return $row;
    }

    public static function findOrCreateGroup(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Nome do grupo é obrigatório.');
        }
        $slug = self::slug((string) ($data['slug'] ?? $name));
        $bySlug = Capsule::table('mod_cvxcatalog_group_meta')->where('slug', $slug)->first();
        if ($bySlug && ($data['on_exists'] ?? 'reuse') !== 'duplicate') {
            self::saveGroupMeta((int) $bySlug->group_id, $data);
            $g = Capsule::table('tblproductgroups')->where('id', $bySlug->group_id)->first();
            return ['id' => (int) $bySlug->group_id, 'created' => false, 'name' => $g->name ?? $name];
        }
        $existing = Capsule::table('tblproductgroups')->where('name', $name)->first();
        if ($existing && ($data['on_exists'] ?? 'reuse') !== 'duplicate') {
            self::saveGroupMeta((int) $existing->id, $data);
            return ['id' => (int) $existing->id, 'created' => false, 'name' => $existing->name];
        }
        if ($existing && ($data['on_exists'] ?? '') === 'duplicate') {
            $name .= ' ' . date('His');
        }

        $payload = ['name' => $name, 'hidden' => !empty($data['hidden']) ? 1 : 0];
        if (self::hasColumn('tblproductgroups', 'headline')) {
            $payload['headline'] = $data['headline'] ?? $data['title'] ?? '';
        }
        if (self::hasColumn('tblproductgroups', 'tagline')) {
            $payload['tagline'] = $data['tagline'] ?? $data['subtitle'] ?? '';
        }
        if (self::hasColumn('tblproductgroups', 'order')) {
            $payload['order'] = (int) ($data['order'] ?? ((int) Capsule::table('tblproductgroups')->max('order') + 1));
        }
        if (self::hasColumn('tblproductgroups', 'disabledgateways')) {
            $payload['disabledgateways'] = '';
        }
        $id = (int) Capsule::table('tblproductgroups')->insertGetId($payload);
        self::saveGroupMeta($id, $data);
        return ['id' => $id, 'created' => true, 'name' => $name];
    }

    public static function saveGroupMeta(int $groupId, array $data): void
    {
        $now = date('Y-m-d H:i:s');
        Capsule::table('mod_cvxcatalog_group_meta')->updateOrInsert(
            ['group_id' => $groupId],
            [
                'slug' => self::slug((string) ($data['slug'] ?? $data['name'] ?? '')),
                'segment' => $data['segment'] ?? null,
                'icon' => $data['icon'] ?? null,
                'title' => $data['title'] ?? $data['headline'] ?? null,
                'subtitle' => $data['subtitle'] ?? $data['tagline'] ?? null,
                'color' => $data['color'] ?? null,
                'featured' => !empty($data['featured']) ? 1 : 0,
                'features_json' => isset($data['features']) ? json_encode($data['features'], JSON_UNESCAPED_UNICODE) : null,
                'updated_at' => $now,
                'created_at' => Capsule::table('mod_cvxcatalog_group_meta')->where('group_id', $groupId)->value('created_at') ?: $now,
            ]
        );
    }

    public static function createProduct(array $data, string $onExists = 'ignore'): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $gid = (int) ($data['gid'] ?? 0);
        if ($name === '' || $gid < 1) {
            throw new \InvalidArgumentException('Produto e grupo são obrigatórios.');
        }

        $existing = Capsule::table('tblproducts')->where('gid', $gid)->where('name', $name)->first();
        if ($existing) {
            if ($onExists === 'ignore') {
                return ['id' => (int) $existing->id, 'created' => false, 'skipped' => true, 'name' => $name];
            }
            if ($onExists === 'update') {
                return self::updateProduct((int) $existing->id, $data);
            }
            $name .= ' ' . date('His');
        }

        $pricing = self::pricingArray($data);
        $api = [
            'name' => $name,
            'gid' => $gid,
            'type' => $data['type'] ?? 'other',
            'paytype' => $data['paytype'] ?? 'recurring',
            'description' => $data['description'] ?? '',
            'hidden' => !empty($data['hidden']) ? '1' : '0',
            'showdomainoptions' => $data['showdomainoptions'] ?? '0',
            'module' => $data['module'] ?? ($data['servertype'] ?? ''),
            'autosetup' => $data['autosetup'] ?? 'payment',
            'welcomeemail' => $data['welcomeemail_name'] ?? '',
            'pricing' => $pricing,
        ];
        if (!empty($data['servergroup'])) {
            $api['servergroupid'] = (int) $data['servergroup'];
        }

        $res = localAPI('AddProduct', $api);
        if (($res['result'] ?? '') !== 'success') {
            $pid = self::createProductDirect($name, $gid, $data);
        } else {
            $pid = (int) ($res['pid'] ?? $res['productid'] ?? 0);
            if ($pid < 1) {
                $row = Capsule::table('tblproducts')->where('gid', $gid)->where('name', $name)->orderBy('id', 'desc')->first();
                $pid = $row ? (int) $row->id : 0;
            }
        }
        if ($pid < 1) {
            throw new \RuntimeException('Não foi possível criar o produto ' . $name);
        }

        self::savePricing($pid, $data);
        self::saveMeta($pid, $data);
        self::saveFeatures($pid, $data['features'] ?? []);
        if (!empty($data['customfields'])) {
            self::saveCustomFields($pid, $data['customfields']);
        }
        self::applyModuleOptions($pid, $data);
        self::bustPublicCache();

        return ['id' => $pid, 'created' => true, 'skipped' => false, 'name' => $name];
    }

    public static function updateProduct(int $id, array $data): array
    {
        $current = Capsule::table('tblproducts')->where('id', $id)->first();
        if (!$current) {
            throw new \RuntimeException('Produto não encontrado.');
        }
        $update = [];
        foreach (['name', 'gid', 'type', 'paytype', 'description', 'hidden', 'retired', 'welcomeemail', 'autosetup', 'servertype', 'servergroup', 'showdomainoptions', 'stockcontrol', 'qty'] as $field) {
            if (array_key_exists($field, $data) && self::hasColumn('tblproducts', $field)) {
                $update[$field] = $data[$field];
            }
        }
        if (isset($data['module']) && self::hasColumn('tblproducts', 'servertype')) {
            $update['servertype'] = $data['module'];
        }
        if (isset($data['short_description']) && self::hasColumn('tblproducts', 'short_description')) {
            $update['short_description'] = $data['short_description'];
        }
        if (isset($data['tagline']) && self::hasColumn('tblproducts', 'tagline')) {
            $update['tagline'] = $data['tagline'];
        }
        if (isset($data['is_featured']) && self::hasColumn('tblproducts', 'is_featured')) {
            $update['is_featured'] = (int) $data['is_featured'];
        }
        if ($update) {
            Capsule::table('tblproducts')->where('id', $id)->update($update);
        }
        self::savePricing($id, $data);
        self::saveMeta($id, $data);
        if (isset($data['features'])) {
            self::saveFeatures($id, $data['features']);
        }
        if (isset($data['customfields'])) {
            self::saveCustomFields($id, $data['customfields']);
        }
        self::applyModuleOptions($id, $data);
        self::bustPublicCache();
        return ['id' => $id, 'created' => false, 'updated' => true, 'name' => $data['name'] ?? $current->name];
    }

    public static function duplicateProduct(int $id, ?string $newName = null): array
    {
        $src = self::product($id);
        if (!$src) {
            throw new \RuntimeException('Produto original não encontrado.');
        }
        $src['name'] = $newName ?: ($src['name'] . ' Cópia');
        unset($src['id']);
        return self::createProduct($src, 'duplicate');
    }

    public static function hideOrRetire(int $id, string $mode): void
    {
        if ($mode === 'retire') {
            Capsule::table('tblproducts')->where('id', $id)->update(['retired' => 1, 'hidden' => 1]);
        } else {
            Capsule::table('tblproducts')->where('id', $id)->update(['hidden' => 1]);
        }
        self::bustPublicCache();
    }

    public static function canDelete(int $id): bool
    {
        return Capsule::table('tblhosting')->where('packageid', $id)->whereIn('domainstatus', ['Active', 'Suspended', 'Pending'])->count() === 0;
    }

    public static function deleteProduct(int $id): void
    {
        if (!self::canDelete($id)) {
            throw new \RuntimeException('Há serviços ativos. Oculte ou descontinue o produto.');
        }
        Capsule::table('tblproducts')->where('id', $id)->delete();
        Capsule::table('tblpricing')->where('type', 'product')->where('relid', $id)->delete();
        Capsule::table('mod_cvxcatalog_product_meta')->where('product_id', $id)->delete();
        Capsule::table('mod_cvxcatalog_features')->where('product_id', $id)->delete();
        self::bustPublicCache();
    }

    public static function adjustPrices(array $ids, float $percent, string $cycles = 'all'): array
    {
        $cid = self::currencyId();
        $fields = $cycles === 'monthly' ? ['monthly'] : ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];
        $preview = [];
        foreach ($ids as $id) {
            $row = Capsule::table('tblpricing')->where('type', 'product')->where('currency', $cid)->where('relid', (int) $id)->first();
            if (!$row) {
                continue;
            }
            $before = [];
            $after = [];
            $upd = [];
            foreach ($fields as $f) {
                $val = (float) $row->$f;
                $before[$f] = $val;
                if ($val > 0) {
                    $new = round($val * (1 + $percent / 100), 2);
                    $upd[$f] = $new;
                    $after[$f] = $new;
                }
            }
            if ($upd) {
                Capsule::table('tblpricing')->where('id', $row->id)->update($upd);
            }
            $preview[] = ['id' => (int) $id, 'before' => $before, 'after' => $after];
        }
        self::bustPublicCache();
        return $preview;
    }

    public static function emails(): array
    {
        return Capsule::table('tblemailtemplates')
            ->where('type', 'product')
            ->orderBy('name')
            ->get(['id', 'name', 'subject'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public static function servers(): array
    {
        $q = Capsule::table('tblservers');
        try {
            if (self::hasColumn('tblservers', 'disabled')) {
                $q->where('disabled', 0);
            } elseif (self::hasColumn('tblservers', 'active')) {
                $q->where('active', 1);
            }
        } catch (\Throwable $e) {
        }
        return $q->get(['id', 'name', 'type', 'hostname'])
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    public static function serverGroups(): array
    {
        if (!Capsule::schema()->hasTable('tblservergroups')) {
            return [];
        }
        return Capsule::table('tblservergroups')->orderBy('name')->get()->map(fn ($r) => (array) $r)->all();
    }

    public static function duplicateGroup(int $gid, string $newName): array
    {
        $src = Capsule::table('tblproductgroups')->where('id', $gid)->first();
        if (!$src) {
            throw new \RuntimeException('Grupo original não encontrado.');
        }
        $meta = Capsule::table('mod_cvxcatalog_group_meta')->where('group_id', $gid)->first();
        $group = self::findOrCreateGroup([
            'name' => $newName,
            'slug' => self::slug($newName),
            'segment' => $meta->segment ?? null,
            'icon' => $meta->icon ?? null,
            'title' => $meta->title ?? ($src->headline ?? $newName),
            'subtitle' => $meta->subtitle ?? ($src->tagline ?? ''),
            'features' => $meta && $meta->features_json ? json_decode($meta->features_json, true) : [],
            'on_exists' => 'duplicate',
        ]);
        $created = [];
        foreach (Capsule::table('tblproducts')->where('gid', $gid)->get() as $p) {
            $data = self::product((int) $p->id);
            if (!$data) {
                continue;
            }
            $data['gid'] = $group['id'];
            unset($data['id']);
            $created[] = self::createProduct($data, 'duplicate');
        }
        return ['group' => $group, 'products' => $created];
    }

    public static function modules(): array
    {
        $dir = ROOTDIR . '/modules/servers';
        $out = [];
        foreach (scandir($dir) ?: [] as $item) {
            if ($item[0] === '.' || $item === 'index.php') {
                continue;
            }
            $out[] = preg_replace('/\.php$/', '', $item);
        }
        sort($out);
        return array_values(array_unique($out));
    }

    public static function knownPackages(): array
    {
        return Capsule::table('tblproducts')
            ->where('configoption1', '!=', '')
            ->distinct()
            ->pluck('configoption1')
            ->all();
    }

    public static function publicCatalog(?string $segment = null): array
    {
        if (!Capsule::schema()->hasTable('mod_cvxcatalog_product_meta')) {
            return [];
        }
        $cid = self::currencyId();
        $q = Capsule::table('tblproducts as p')
            ->join('tblproductgroups as g', 'g.id', '=', 'p.gid')
            ->leftJoin('tblpricing as pr', function ($j) use ($cid) {
                $j->on('pr.relid', '=', 'p.id')->where('pr.type', '=', 'product')->where('pr.currency', '=', $cid);
            })
            ->leftJoin('mod_cvxcatalog_product_meta as m', 'm.product_id', '=', 'p.id')
            ->leftJoin('mod_cvxcatalog_group_meta as gm', 'gm.group_id', '=', 'g.id')
            ->where('p.hidden', 0)
            ->where('p.retired', 0)
            ->where('g.hidden', 0)
            ->where(function ($w) {
                $w->whereNull('m.status')->orWhere('m.status', 'published');
            });
        if ($segment) {
            $q->where(function ($w) use ($segment) {
                $w->where('gm.slug', $segment)->orWhere('gm.segment', $segment)->orWhere('m.segment', $segment);
            });
        }
        $rows = $q->select(
            'p.id', 'p.name', 'p.description', 'p.type', 'p.paytype',
            'g.id as group_id', 'g.name as group_name', 'g.headline', 'g.tagline',
            'pr.monthly', 'pr.annually', 'pr.quarterly',
            'm.slogan', 'm.badge', 'm.slug', 'm.segment', 'm.compare_monthly', 'm.compare_annually',
            'm.location', 'm.location_label', 'm.discount_percentage', 'm.promotion_label', 'm.sort_order',
            'gm.slug as group_slug', 'gm.icon as group_icon', 'gm.segment as group_segment'
        )->orderBy('g.order')->orderBy('p.id')->get();

        $ids = $rows->pluck('id')->all();
        $feats = [];
        if ($ids) {
            foreach (Capsule::table('mod_cvxcatalog_features')->whereIn('product_id', $ids)->orderBy('sort_order')->get() as $f) {
                $feats[$f->product_id][] = (array) $f;
            }
        }

        return $rows->map(function ($r) use ($feats) {
            $row = (array) $r;
            $row['features'] = $feats[$r->id] ?? [];
            $row['order_url'] = '/cart.php?a=add&pid=' . (int) $r->id;
            return $row;
        })->all();
    }

    public static function log(string $action, array $ctx = []): void
    {
        Capsule::table('mod_cvxcatalog_logs')->insert([
            'batch_id' => $ctx['batch_id'] ?? null,
            'admin_id' => $ctx['admin_id'] ?? ($_SESSION['adminid'] ?? null),
            'action' => $action,
            'product_id' => $ctx['product_id'] ?? null,
            'product_name' => $ctx['product_name'] ?? null,
            'before_json' => isset($ctx['before']) ? json_encode($ctx['before'], JSON_UNESCAPED_UNICODE) : null,
            'after_json' => isset($ctx['after']) ? json_encode($ctx['after'], JSON_UNESCAPED_UNICODE) : null,
            'message' => $ctx['message'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function createProductDirect(string $name, int $gid, array $data): int
    {
        $payload = [
            'name' => $name,
            'gid' => $gid,
            'type' => $data['type'] ?? 'other',
            'paytype' => $data['paytype'] ?? 'recurring',
            'description' => $data['description'] ?? '',
            'hidden' => !empty($data['hidden']) ? 1 : 0,
            'retired' => 0,
            'showdomainoptions' => (int) ($data['showdomainoptions'] ?? 0),
            'welcomeemail' => is_numeric($data['welcomeemail'] ?? null) ? (int) $data['welcomeemail'] : 0,
            'autosetup' => $data['autosetup'] ?? 'payment',
            'servertype' => $data['module'] ?? '',
            'servergroup' => (int) ($data['servergroup'] ?? 0),
            'stockcontrol' => 0,
            'qty' => 0,
        ];
        $filtered = [];
        foreach ($payload as $k => $v) {
            if (self::hasColumn('tblproducts', $k)) {
                $filtered[$k] = $v;
            }
        }
        return (int) Capsule::table('tblproducts')->insertGetId($filtered);
    }

    private static function savePricing(int $pid, array $data): void
    {
        $cid = self::currencyId();
        $cycles = ['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'];
        $row = ['type' => 'product', 'currency' => $cid, 'relid' => $pid];
        foreach ($cycles as $c) {
            $val = $data[$c] ?? null;
            $row[$c] = ($val === null || $val === '' || (float) $val <= 0) ? -1.00 : (float) $val;
            $row[substr($c, 0, 1) . 'setupfee'] = (float) ($data[$c . '_setup'] ?? $data['msetupfee'] ?? 0);
        }
        $exists = Capsule::table('tblpricing')->where('type', 'product')->where('currency', $cid)->where('relid', $pid)->first();
        if ($exists) {
            Capsule::table('tblpricing')->where('id', $exists->id)->update($row);
        } else {
            Capsule::table('tblpricing')->insert($row);
        }
    }

    private static function saveMeta(int $pid, array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $meta = [
            'slug' => self::slug((string) ($data['slug'] ?? $data['name'] ?? '')),
            'segment' => $data['segment'] ?? null,
            'slogan' => $data['slogan'] ?? $data['short_description'] ?? null,
            'badge' => $data['badge'] ?? null,
            'icon' => $data['icon'] ?? null,
            'image' => $data['image'] ?? null,
            'color' => $data['color'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'compare_monthly' => $data['compare_monthly'] ?: null,
            'compare_annually' => $data['compare_annually'] ?: null,
            'status' => $data['pub_status'] ?? 'published',
            'updated_at' => $now,
            'created_at' => Capsule::table('mod_cvxcatalog_product_meta')->where('product_id', $pid)->value('created_at') ?: $now,
        ];
        foreach (['location', 'location_label', 'promotion_label'] as $col) {
            if (self::hasColumn('mod_cvxcatalog_product_meta', $col)) {
                $meta[$col] = $data[$col] ?? null;
            }
        }
        if (self::hasColumn('mod_cvxcatalog_product_meta', 'discount_percentage')) {
            $meta['discount_percentage'] = $data['discount_percentage'] ?: null;
        }
        if (self::hasColumn('mod_cvxcatalog_product_meta', 'sort_order')) {
            $meta['sort_order'] = (int) ($data['sort_order'] ?? 0);
        }
        Capsule::table('mod_cvxcatalog_product_meta')->updateOrInsert(['product_id' => $pid], $meta);
    }

    private static function saveFeatures(int $pid, array $features): void
    {
        Capsule::table('mod_cvxcatalog_features')->where('product_id', $pid)->delete();
        $i = 0;
        foreach ($features as $f) {
            if (is_string($f)) {
                $f = ['name' => $f];
            }
            $name = trim((string) ($f['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $row = [
                'product_id' => $pid,
                'name' => $name,
                'value' => $f['value'] ?? null,
                'icon' => $f['icon'] ?? 'fa-solid fa-check',
                'sort_order' => (int) ($f['sort_order'] ?? $i++),
                'highlight' => !empty($f['highlight']) ? 1 : 0,
            ];
            if (self::hasColumn('mod_cvxcatalog_features', 'unit')) {
                $row['unit'] = $f['unit'] ?? null;
            }
            Capsule::table('mod_cvxcatalog_features')->insert($row);
        }
    }

    private static function applyModuleOptions(int $pid, array $data): void
    {
        $upd = [];
        if (isset($data['package']) && self::hasColumn('tblproducts', 'configoption1')) {
            $upd['configoption1'] = $data['package'];
        }
        if ($upd) {
            Capsule::table('tblproducts')->where('id', $pid)->update($upd);
        }
    }

    private static function pricingArray(array $data): array
    {
        $cid = (string) self::currencyId();
        $map = [];
        foreach (['monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'] as $c) {
            $map[$c] = isset($data[$c]) && (float) $data[$c] > 0 ? (float) $data[$c] : -1;
        }
        return [$cid => $map];
    }

    public static function slug(string $text): string
    {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text) ?? '');
        return trim($text, '-') ?: 'produto';
    }

    private static function saveCustomFields(int $pid, array $fields): void
    {
        foreach ($fields as $f) {
            $name = trim((string) ($f['name'] ?? $f['fieldname'] ?? ''));
            if ($name === '') {
                continue;
            }
            $row = [
                'type' => 'product',
                'relid' => $pid,
                'fieldname' => $name,
                'fieldtype' => $f['type'] ?? $f['fieldtype'] ?? 'text',
                'description' => $f['description'] ?? '',
                'fieldoptions' => $f['options'] ?? $f['fieldoptions'] ?? '',
                'regexpr' => $f['validation'] ?? $f['regexpr'] ?? '',
                'adminonly' => !empty($f['adminonly']) ? 'on' : '',
                'required' => !empty($f['required']) ? 'on' : '',
                'showorder' => !empty($f['showorder']) ? 'on' : '',
                'showinvoice' => !empty($f['showinvoice']) ? 'on' : '',
                'sortorder' => (int) ($f['sortorder'] ?? $f['sort_order'] ?? 0),
            ];
            $filtered = [];
            foreach ($row as $k => $v) {
                if (self::hasColumn('tblcustomfields', $k)) {
                    $filtered[$k] = $v;
                }
            }
            $id = (int) ($f['id'] ?? 0);
            if ($id > 0) {
                Capsule::table('tblcustomfields')->where('id', $id)->where('relid', $pid)->update($filtered);
            } else {
                Capsule::table('tblcustomfields')->insert($filtered);
            }
        }
    }

    public static function bustPublicCache(): void
    {
        $helper = ROOTDIR . '/includes/centralvix-whmcs-catalog.php';
        if (is_file($helper)) {
            require_once $helper;
            if (function_exists('centralvix_catalog_bust_cache')) {
                centralvix_catalog_bust_cache();
            }
        }
    }

    private static function hasColumn(string $table, string $column): bool
    {
        static $cache = [];
        $key = $table . '.' . $column;
        if (!isset($cache[$key])) {
            $cache[$key] = Capsule::schema()->hasColumn($table, $column);
        }
        return $cache[$key];
    }
}
