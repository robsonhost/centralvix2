<?php

namespace Cvx\Catalog;

final class Segments
{
    public static function all(): array
    {
        $items = [
            'hospedagem' => self::item('hospedagem', 'Hospedagem de Sites', 'fa-solid fa-server', 'hostingaccount', 'cpanel', 'CentralVix — Hospedagem Ativada', ['Turbo 1', 'Turbo 2', 'Turbo 3', 'X-Turbo'], ['Memória Virtual', 'Domínios', 'Tráfego Mensal', 'Armazenamento', 'SSL', 'Subdomínios', 'E-mail']),
            'revenda' => self::item('revenda', 'Revenda WHM/cPanel', 'fa-solid fa-network-wired', 'reselleraccount', 'cpanel', 'CentralVix — Revenda Ativada', ['TURBO-WHM-REV1', 'TURBO-WHM-REV2', 'TURBO-WHM-REV3', 'TURBO-WHM-REV4'], ['Contas cPanel', 'SSD NVMe', 'LiteSpeed Premium', 'Transferência', 'Painel WHM', 'Certificado SSL']),
            'vps-brasil' => self::item('vps-brasil', 'VPS Brasil', 'fa-solid fa-hard-drive', 'server', '', 'CentralVix — VPS Ativado', ['VPS BR 1', 'VPS BR 2', 'VPS BR 3', 'VPS BR 4'], ['vCPU', 'RAM', 'Armazenamento', 'Transferência', 'cPanel', 'IP Dedicado', 'Migração']),
            'vps-canada' => self::item('vps-canada', 'VPS Canadá', 'fa-solid fa-globe', 'server', '', 'CentralVix — VPS Ativado', ['VPS 1', 'VPS 2', 'VPS 3', 'VPS 4', 'VPS 5', 'VPS 6', 'VPS 7', 'VPS 8', 'VPS 9'], ['vCPU', 'RAM', 'Armazenamento', 'Transferência', 'Anti-DDoS', 'IP Dedicado']),
            'vps-internacional' => self::item('vps-internacional', 'VPS Canadá', 'fa-solid fa-globe', 'server', '', 'CentralVix — VPS Ativado', ['VPS 1', 'VPS 2', 'VPS 3', 'VPS 4', 'VPS 5', 'VPS 6', 'VPS 7', 'VPS 8', 'VPS 9'], ['vCPU', 'RAM', 'Armazenamento', 'Transferência', 'Anti-DDoS', 'IP Dedicado']),
            'dedicado' => self::item('dedicado', 'Servidor Dedicado', 'fa-solid fa-database', 'server', '', 'CentralVix — VPS Ativado', ['Dedicado Start', 'Dedicado Pro', 'Dedicado Business', 'Dedicado Enterprise'], ['CPU', 'RAM', 'Disco', 'Porta', 'IPv4', 'IPMI', 'Anti-DDoS', 'Suporte']),
            'streaming' => self::item('streaming', 'Streaming de Áudio', 'fa-solid fa-broadcast-tower', 'other', 'centovacast', 'CentralVix — Streaming Ativado', ['Streaming Start', 'Streaming Pro', 'Streaming Business', 'Streaming Enterprise'], ['Ouvintes', 'Qualidade', 'Espaço AutoDJ', 'Transferência', 'SSL', 'Painel', 'Player', 'Estatísticas']),
            'revenda-streaming' => self::item('revenda-streaming', 'Revenda de Streaming', 'fa-solid fa-tower-broadcast', 'other', 'centovacast', 'CentralVix — Streaming Ativado', ['Revenda Streaming 10', 'Revenda Streaming 25', 'Revenda Streaming 50', 'Revenda Streaming 100'], ['Canais', 'Ouvintes', 'AutoDJ', 'Painel', 'Marca branca', 'SSL', 'Suporte']),
            'web-radio' => self::item('web-radio', 'Web Rádio', 'fa-solid fa-radio', 'other', '', 'CentralVix — Web Rádio Ativada', ['Web Rádio Start', 'Web Rádio Pro', 'Web Rádio Business', 'Web Rádio Premium'], ['Streaming', 'AutoDJ', 'Site', 'Player', 'SSL', 'Aplicativo', 'Painel']),
            'app-radio' => self::item('app-radio', 'Aplicativo para Rádio', 'fa-solid fa-mobile-screen', 'other', '', 'CentralVix — Aplicativo Contratado', ['App Start', 'App Pro', 'App Business', 'App Premium'], ['Android', 'iOS', 'Painel', 'Push', 'Design', 'Player', 'Suporte']),
            'app-android' => self::item('app-android', 'Aplicativo Android', 'fa-brands fa-android', 'other', '', 'CentralVix — Aplicativo Contratado', ['App Start', 'App Pro', 'App Business', 'App Premium'], ['Android', 'Painel', 'Push', 'Design', 'Publicação', 'Suporte']),
            'app-ios' => self::item('app-ios', 'Aplicativo iOS', 'fa-brands fa-apple', 'other', '', 'CentralVix — Aplicativo Contratado', ['App Start', 'App Pro', 'App Business', 'App Premium'], ['iOS', 'Painel', 'Push', 'Design', 'Publicação', 'Suporte']),
            'site' => self::item('site', 'Desenvolvimento de Site', 'fa-solid fa-laptop-code', 'other', '', 'CentralVix — Site Contratado', ['Site Essencial', 'Site Profissional', 'Site Business', 'Site Premium'], ['Páginas', 'Responsivo', 'SSL', 'SEO básico', 'WhatsApp', 'Formulário', 'Hospedagem', 'Manutenção']),
            'loja' => self::item('loja', 'Loja Virtual', 'fa-solid fa-cart-shopping', 'other', '', 'CentralVix — Site Contratado', ['Loja Start', 'Loja Pro', 'Loja Business', 'Loja Enterprise'], ['Produtos', 'Pagamentos', 'Responsivo', 'SSL', 'SEO', 'Estoque', 'Suporte']),
            'landing' => self::item('landing', 'Landing Page', 'fa-solid fa-window-maximize', 'other', '', 'CentralVix — Site Contratado', ['Landing Essencial', 'Landing Pro', 'Landing Business', 'Landing Premium'], ['Seções', 'Responsivo', 'Formulário', 'WhatsApp', 'SSL', 'SEO']),
            'dev-app' => self::item('dev-app', 'Desenvolvimento de Aplicativo', 'fa-solid fa-code', 'other', '', 'CentralVix — Aplicativo Contratado', ['Aplicativo Básico', 'Aplicativo Profissional', 'Aplicativo Business', 'Projeto Personalizado'], ['Android', 'iOS', 'Painel', 'Design', 'Publicação', 'Suporte']),
            'sistema' => self::item('sistema', 'Sistema Web', 'fa-solid fa-gears', 'other', '', 'CentralVix — Sistema Contratado', ['Sistema Start', 'Sistema Pro', 'Sistema Business', 'Sistema Enterprise'], ['Módulos', 'Usuários', 'Painel', 'Relatórios', 'API', 'Suporte']),
            'automacao' => self::item('automacao', 'Automação', 'fa-solid fa-robot', 'other', '', 'CentralVix — Sistema Contratado', ['Automação Start', 'Automação Pro', 'Automação Business', 'Automação Enterprise'], ['Fluxos', 'Integrações', 'WhatsApp', 'Relatórios', 'Suporte']),
            'licenca' => self::item('licenca', 'Licença de Software', 'fa-solid fa-key', 'other', 'licensing', 'Other Product/Service Welcome Email', ['Licença Mensal', 'Licença Trimestral', 'Licença Anual', 'Licença Enterprise'], ['Ativações', 'Atualizações', 'Suporte', 'Painel']),
            'manutencao' => self::item('manutencao', 'Manutenção', 'fa-solid fa-screwdriver-wrench', 'other', '', 'Other Product/Service Welcome Email', ['Manutenção Start', 'Manutenção Pro', 'Manutenção Business', 'Manutenção Enterprise'], ['Horas', 'Backup', 'Atualizações', 'Monitoramento', 'Suporte']),
            'suporte' => self::item('suporte', 'Suporte / Manutenção', 'fa-solid fa-headset', 'other', '', 'Other Product/Service Welcome Email', ['Suporte Start', 'Suporte Pro', 'Suporte Business', 'Suporte Enterprise'], ['Canal', 'SLA', 'Horário', 'Prioridade']),
        ];
        foreach ($items as $slug => &$item) {
            $catalog = CatalogPresets::for($slug);
            $item['ready'] = $catalog !== [];
            $item['catalog'] = $catalog;
            if ($catalog) {
                $item['plans'] = array_values(array_map(static fn(array $p) => $p['name'], $catalog));
            }
        }
        unset($item);
        return $items;
    }

    private static function item(string $slug, string $name, string $icon, string $type, string $module, string $email, array $plans, array $features): array
    {
        return [
            'slug' => $slug,
            'name' => $name,
            'icon' => $icon,
            'type' => $type,
            'module' => $module,
            'email' => $email,
            'headline' => $name . ' profissional para o seu projeto',
            'tagline' => 'Alta performance, segurança e suporte especializado.',
            'plans' => $plans,
            'features' => $features,
        ];
    }
}
