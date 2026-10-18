<?php

namespace Cvx\Catalog;

final class TextFactory
{
    public static function short(string $name, string $segment = ''): string
    {
        $base = [
            'hospedagem' => 'Plano de hospedagem rápido e seguro, ideal para sites profissionais e pequenos negócios.',
            'revenda' => 'Revenda de hospedagem com painel WHM, pronta para você atender os seus próprios clientes.',
            'vps-brasil' => 'VPS no Brasil com recursos dedicados, controle total e estabilidade para projetos exigentes.',
            'vps-internacional' => 'VPS internacional com alta performance, IP dedicado e acesso root.',
            'streaming' => 'Streaming de áudio estável para a sua rádio alcançar ouvintes com qualidade profissional.',
            'web-radio' => 'Solução completa de web rádio com streaming, player e ferramentas de divulgação.',
            'site' => 'Site profissional, responsivo e preparado para gerar contato e presença digital.',
            'loja' => 'Loja virtual completa para vender online com segurança e organização.',
            'sistema' => 'Sistema web sob medida para organizar processos, usuários e relatórios.',
            'licenca' => 'Licença de software com validade controlada e liberação automática após o pagamento.',
        ];
        return $base[$segment] ?? ('Solução CentralVix ' . $name . ' com suporte especializado e contratação simples.');
    }

    public static function long(string $name, array $features = [], string $segment = ''): string
    {
        $intro = '<h3>' . htmlspecialchars($name) . '</h3><p>' . htmlspecialchars(self::short($name, $segment)) . '</p>';
        if ($segment === 'hospedagem') {
            $intro .= '<p>SSD NVMe, cPanel, SSL e suporte CentralVix inclusos no plano.</p>';
        } elseif ($segment === 'revenda') {
            $intro .= '<p>Painel WHM, contas cPanel e LiteSpeed para você revender com a sua marca.</p>';
        } elseif (str_starts_with($segment, 'vps')) {
            $intro .= '<p>Recursos dedicados, acesso root, IP próprio e proteção Anti-DDoS.</p>';
        }
        $list = '';
        foreach ($features as $f) {
            $label = is_array($f) ? ($f['name'] . (!empty($f['value']) ? ': ' . $f['value'] : '')) : (string) $f;
            $list .= '<li>' . htmlspecialchars($label) . '</li>';
        }
        if ($list === '') {
            $list = '<li>Suporte CentralVix</li><li>Ativação após pagamento</li>';
        }
        return $intro . '<ul>' . $list . '</ul><p>Contrate agora e comece com a equipe CentralVix ao seu lado.</p>';
    }
}
