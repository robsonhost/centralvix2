<?php

namespace Cvx\Catalog;

/**
 * Catálogo comercial inicial CentralVix — 21 produtos oficiais.
 * Preços e recursos vêm deste documento. Anual de hospedagem fica pendente.
 */
final class OfficialCatalog
{
    public static function groups(): array
    {
        return [
            'hospedagem' => [
                'slug' => 'hospedagem',
                'name' => 'Hospedagem de Sites',
                'segment' => 'hospedagem',
                'title' => 'Escolha o plano ideal para você',
                'subtitle' => 'Hospedagem profissional com recursos premium, segurança reforçada e performance máxima para o seu negócio.',
                'icon' => 'fa-solid fa-server',
                'type' => 'hostingaccount',
                'email' => 'CentralVix — Hospedagem Ativada',
                'order' => 1,
            ],
            'revenda-whm-cpanel' => [
                'slug' => 'revenda-whm-cpanel',
                'name' => 'Revenda WHM/cPanel',
                'segment' => 'revenda',
                'title' => 'Escolha o plano ideal para sua Revenda',
                'subtitle' => 'Tenha sua própria empresa de hospedagem com suporte humanizado e infraestrutura profissional.',
                'icon' => 'fa-solid fa-network-wired',
                'type' => 'reselleraccount',
                'email' => 'CentralVix — Revenda Ativada',
                'order' => 2,
            ],
            'vps-brasil' => [
                'slug' => 'vps-brasil',
                'name' => 'Servidores VPS Brasil',
                'segment' => 'vps-brasil',
                'title' => 'Escolha a configuração ideal para seu VPS',
                'subtitle' => 'Servidores no Brasil com baixa latência para o público brasileiro.',
                'icon' => 'fa-solid fa-hard-drive',
                'type' => 'server',
                'email' => 'CentralVix — VPS Ativado',
                'location' => 'brasil',
                'location_label' => 'Brasil',
                'order' => 3,
            ],
            'vps-canada' => [
                'slug' => 'vps-canada',
                'name' => 'Servidores VPS Canadá',
                'segment' => 'vps-canada',
                'title' => 'Infraestrutura internacional de alta capacidade',
                'subtitle' => 'Servidores no Canadá com proteção Anti-DDoS e IP dedicado.',
                'icon' => 'fa-solid fa-globe',
                'type' => 'server',
                'email' => 'CentralVix — VPS Ativado',
                'location' => 'canada',
                'location_label' => 'Canadá',
                'order' => 4,
            ],
        ];
    }

    public static function products(): array
    {
        return array_merge(
            self::hospedagem(),
            self::revenda(),
            self::vpsBrasil(),
            self::vpsCanada()
        );
    }

    public static function count(): int
    {
        return count(self::products());
    }

    private static function hospedagem(): array
    {
        $g = 'hospedagem';
        return [
            self::item($g, 'Turbo 1', 'turbo-1', 'Ideal para quem está começando o primeiro site ou blog.', 15.99, null, null, 0, '', 0, 1, [
                self::feat('Memória Virtual', '1', 'GB'),
                self::feat('Domínios', '1'),
                self::feat('Tráfego Mensal', '10', 'GB'),
                self::feat('Armazenamento', '20', 'GB'),
                self::feat('SSL', 'Gratuito'),
                self::feat('Subdomínios', '5'),
                self::feat('E-mail', 'Ilimitado'),
            ]),
            self::item($g, 'Turbo 2', 'turbo-2', 'A escolha perfeita para sites empresariais e lojas virtuais.', 29.99, null, null, 0, 'O MAIS VENDIDO', 1, 2, [
                self::feat('Memória Virtual', '2', 'GB'),
                self::feat('Domínios', '1'),
                self::feat('Tráfego Mensal', 'Ilimitado'),
                self::feat('Armazenamento', '50', 'GB'),
                self::feat('SSL', 'Gratuito'),
                self::feat('Subdomínios', 'Ilimitados'),
                self::feat('E-mail', 'Ilimitado'),
            ]),
            self::item($g, 'Turbo 3', 'turbo-3', 'Para agências e sites em pleno crescimento que exigem mais.', 49.99, null, null, 0, '', 0, 3, [
                self::feat('Memória Virtual', '3', 'GB'),
                self::feat('Domínios', '4'),
                self::feat('Tráfego Mensal', 'Ilimitado'),
                self::feat('Armazenamento', '100', 'GB'),
                self::feat('SSL', 'Gratuito'),
                self::feat('Subdomínios', 'Ilimitados'),
                self::feat('E-mail', 'Ilimitado'),
            ]),
            self::item($g, 'X-Turbo', 'x-turbo', 'Performance extrema para projetos que precisam de mais recursos.', 89.99, null, null, 0, '', 0, 4, [
                self::feat('Memória Virtual', '6', 'GB'),
                self::feat('Domínios', '6'),
                self::feat('Tráfego Mensal', 'Ilimitado'),
                self::feat('Armazenamento', '150', 'GB'),
                self::feat('SSL', 'Gratuito'),
                self::feat('Subdomínios', 'Ilimitados'),
                self::feat('E-mail', 'Ilimitado'),
            ]),
        ];
    }

    private static function revenda(): array
    {
        $g = 'revenda-whm-cpanel';
        $comum = [
            self::feat('LiteSpeed Premium', 'Incluso'),
            self::feat('Transferência', 'Ilimitada'),
            self::feat('Painel WHM', 'Completo'),
            self::feat('Certificado SSL', 'Grátis'),
        ];
        return [
            self::item($g, 'TURBO-WHM-REV1', 'turbo-whm-rev1', 'O plano de entrada perfeito para quem está começando a sua revenda.', 120, 1008, 84, 30, '', 0, 1, array_merge([
                self::feat('Contas cPanel', '15'),
                self::feat('SSD NVMe', '40', 'GB'),
            ], $comum)),
            self::item($g, 'TURBO-WHM-REV2', 'turbo-whm-rev2', 'O melhor custo-benefício para pequenas agências ou freelancers.', 200, 1560, 130, 35, 'MAIS POPULAR', 1, 2, array_merge([
                self::feat('Contas cPanel', '35'),
                self::feat('SSD NVMe', '100', 'GB'),
            ], $comum)),
            self::item($g, 'TURBO-WHM-REV3', 'turbo-whm-rev3', 'Espaço extra para negócios em expansão e grande portfólio.', 390, 2808, 234, 40, '', 0, 3, array_merge([
                self::feat('Contas cPanel', '60'),
                self::feat('SSD NVMe', '300', 'GB'),
            ], $comum)),
            self::item($g, 'TURBO-WHM-REV4', 'turbo-whm-rev4', 'Potência para grandes demandas e alto volume de clientes.', 780, 5616, 468, 40, '', 0, 4, array_merge([
                self::feat('Contas cPanel', '120'),
                self::feat('SSD NVMe', '600', 'GB'),
            ], $comum)),
        ];
    }

    private static function vpsBrasil(): array
    {
        $g = 'vps-brasil';
        $extra = [
            self::feat('Transferência', 'Ilimitada'),
            self::feat('Servidores Cloud', 'Brasil'),
            self::feat('cPanel', 'Disponível'),
            self::feat('IP Dedicado', '1'),
            self::feat('Migração', 'Grátis'),
        ];
        return [
            self::item($g, 'VPS BR 1', 'vps-br-1', 'VPS no Brasil com baixa latência.', 49.99, 599.88, 99.98, 50, '', 0, 1, array_merge([
                self::feat('vCPU', '2'),
                self::feat('RAM', '2', 'GB'),
                self::feat('Armazenamento', '25', 'GB NVMe'),
            ], $extra), 'brasil', 'Brasil'),
            self::item($g, 'VPS BR 2', 'vps-br-2', 'VPS no Brasil para projetos em crescimento.', 89, 1068, 178, 50, 'MAIS POPULAR', 1, 2, array_merge([
                self::feat('vCPU', '4'),
                self::feat('RAM', '4', 'GB'),
                self::feat('Armazenamento', '40', 'GB NVMe'),
            ], $extra), 'brasil', 'Brasil'),
            self::item($g, 'VPS BR 3', 'vps-br-3', 'VPS recomendado para cargas maiores.', 159, 1908, 318, 50, 'RECOMENDADO', 0, 3, array_merge([
                self::feat('vCPU', '6'),
                self::feat('RAM', '8', 'GB'),
                self::feat('Armazenamento', '60', 'GB NVMe'),
            ], $extra), 'brasil', 'Brasil'),
            self::item($g, 'VPS BR 4', 'vps-br-4', 'VPS Brasil de alta capacidade.', 230, 2760, 460, 50, '', 0, 4, array_merge([
                self::feat('vCPU', '8'),
                self::feat('RAM', '12', 'GB'),
                self::feat('Armazenamento', '100', 'GB NVMe'),
            ], $extra), 'brasil', 'Brasil'),
        ];
    }

    private static function vpsCanada(): array
    {
        $g = 'vps-canada';
        $extra = [
            self::feat('Transferência', 'Ilimitada'),
            self::feat('Proteção Anti-DDoS', 'Incluso'),
            self::feat('Servidores', 'Canadá'),
            self::feat('IP Dedicado', '1'),
        ];
        $rows = [
            ['VPS 1', 'vps-ca-1', 29, 348, 58, 1, '1', '20', '', 0, 1],
            ['VPS 2', 'vps-ca-2', 40, 480, 80, 1, '2', '40', 'MAIS POPULAR', 1, 2],
            ['VPS 3', 'vps-ca-3', 60, 720, 120, 2, '4', '50', '', 0, 3],
            ['VPS 4', 'vps-ca-4', 100, 1200, 200, 4, '8', '80', '', 0, 4],
            ['VPS 5', 'vps-ca-5', 180, 2160, 360, 6, '12', '150', '', 0, 5],
            ['VPS 6', 'vps-ca-6', 200, 2400, 400, 8, '16', '150', '', 0, 6],
            ['VPS 7', 'vps-ca-7', 270, 3240, 540, 12, '24', '300', '', 0, 7],
            ['VPS 8', 'vps-ca-8', 400, 4800, 800, 16, '32', '700', '', 0, 8],
            ['VPS 9', 'vps-ca-9', 550, 6600, 1100, 20, '48', '1000', '', 0, 9],
        ];
        $out = [];
        foreach ($rows as $r) {
            $ssd = $r[7] === '1000' ? '1 TB' : $r[7] . ' GB';
            $out[] = self::item($g, $r[0], $r[1], 'VPS no Canadá com proteção Anti-DDoS.', (float) $r[2], (float) $r[3], (float) $r[4], 50, $r[8], (int) $r[9], (int) $r[10], array_merge([
                self::feat('vCPU', (string) $r[5]),
                self::feat('RAM', $r[6], 'GB'),
                self::feat('Armazenamento', $ssd, 'SSD'),
            ], $extra), 'canada', 'Canadá');
        }
        return $out;
    }

    private static function item(
        string $group,
        string $name,
        string $slug,
        string $short,
        float $monthly,
        ?float $annually,
        ?float $compareMonthly,
        int $discount,
        string $badge,
        int $featured,
        int $order,
        array $features,
        ?string $location = null,
        ?string $locationLabel = null
    ): array {
        return [
            'group_slug' => $group,
            'name' => $name,
            'slug' => $slug,
            'short' => $short,
            'monthly' => $monthly,
            'annually' => $annually,
            'compare_monthly' => $compareMonthly,
            'discount_percentage' => $discount,
            'badge' => $badge,
            'featured' => $featured,
            'sort_order' => $order,
            'features' => $features,
            'location' => $location,
            'location_label' => $locationLabel,
        ];
    }

    private static function feat(string $name, string $value, string $unit = ''): array
    {
        return ['name' => $name, 'value' => $value, 'unit' => $unit];
    }
}
