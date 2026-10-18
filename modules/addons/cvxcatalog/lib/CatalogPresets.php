<?php

namespace Cvx\Catalog;

/** Ponte do wizard para o catálogo oficial de 21 planos. */
final class CatalogPresets
{
    public static function for(string $slug): array
    {
        $map = [
            'hospedagem' => 'hospedagem',
            'revenda' => 'revenda-whm-cpanel',
            'vps-brasil' => 'vps-brasil',
            'vps-canada' => 'vps-canada',
            'vps-internacional' => 'vps-canada',
        ];
        $group = $map[$slug] ?? $slug;
        $out = [];
        foreach (OfficialCatalog::products() as $p) {
            if ($p['group_slug'] !== $group) {
                continue;
            }
            $feats = [];
            foreach ($p['features'] as $f) {
                $label = trim(($f['value'] ?? '') . ' ' . ($f['unit'] ?? ''));
                $feats[$f['name']] = trim($label) !== '' ? trim($label) : ($f['value'] ?? '');
            }
            $out[] = [
                'name' => $p['name'],
                'monthly' => $p['monthly'],
                'quarterly' => '',
                'annually' => $p['annually'] ?? '',
                'featured' => $p['featured'],
                'features' => $feats,
            ];
        }
        return $out;
    }

    public static function all(): array
    {
        $all = [];
        foreach (['hospedagem', 'revenda', 'vps-brasil', 'vps-canada'] as $slug) {
            $all[$slug] = self::for($slug);
        }
        return $all;
    }
}
