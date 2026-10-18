<?php

/**
 * CentralVix — configuração da área pública.
 * Edite este arquivo para alterar textos, estatísticas, planos e contatos
 * sem mexer no HTML.
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

return [
    'brand' => 'CENTRALVIX',
    'brand_prefix' => 'CENTRAL',
    'brand_accent' => 'VIX',
    'tagline' => 'AUTOMAÇÃO & SISTEMAS',
    'badge' => 'SISTEMA CENTRALVIX',

    'hero_title_before' => 'Tecnologia e automação',
    'hero_title_highlight' => 'crescer',
    'hero_title_after' => 'para o seu negócio',
    'hero_text' => 'Hospedagem, VPS, streaming e automação em um único painel: contrate, gerencie e acompanhe sua operação com segurança e sem burocracia.',

    'about_lead' => 'A CENTRALVIX nasceu para simplificar a operação de provedores, revendedores e empresas que precisam de um painel único, estável e profissional.',
    'about_text' => 'Unimos automação, faturamento, suporte e relatórios em uma plataforma pensada para o dia a dia brasileiro: segunda via, cadastro simples, atendimento humano e um painel que o cliente realmente consegue usar.',

    'home_about_kicker' => 'A CENTRALVIX',
    'home_about_title' => 'Automação, controle e suporte em um só painel',
    'home_about_points' => [
        ['icon' => 'fas fa-bolt', 'text' => 'Ativação rápida dos serviços contratados'],
        ['icon' => 'fas fa-file-invoice', 'text' => 'Segunda via e pagamentos direto no painel'],
        ['icon' => 'fas fa-headset', 'text' => 'Atendimento humano em horário comercial'],
    ],

    'trust' => [
        [
            'title' => 'Conexão criptografada',
            'text' => 'Certificado SSL em todas as páginas e área do cliente.',
            'icon' => 'fas fa-lock',
        ],
        [
            'title' => 'Dados protegidos',
            'text' => 'Informações tratadas conforme a LGPD e boas práticas de segurança.',
            'icon' => 'fas fa-user-shield',
        ],
        [
            'title' => 'Suporte humano',
            'text' => 'Atendimento por tickets com retorno em horário comercial.',
            'icon' => 'fas fa-headset',
        ],
        [
            'title' => 'Transparência',
            'text' => 'Faturas, serviços e histórico disponíveis no seu painel.',
            'icon' => 'fas fa-receipt',
        ],
    ],

    'stats' => [
        [
            'value' => '+5.000',
            'label' => 'Clientes ativos',
            'icon' => 'fas fa-users',
        ],
        [
            'value' => '+98%',
            'label' => 'Satisfação',
            'icon' => 'fas fa-check-circle',
        ],
        [
            'value' => '99,9%',
            'label' => 'Disponibilidade',
            'icon' => 'fas fa-clock',
        ],
    ],

    'differentials' => [
        [
            'title' => 'Automação',
            'text' => 'Processos automáticos e inteligentes',
            'icon' => 'fas fa-cogs',
        ],
        [
            'title' => 'Relatórios',
            'text' => 'Dashboards completos e em tempo real',
            'icon' => 'fas fa-chart-line',
        ],
        [
            'title' => 'Segurança',
            'text' => 'Dados protegidos com alta tecnologia',
            'icon' => 'fas fa-shield-alt',
        ],
        [
            'title' => 'Integrações',
            'text' => 'Compatível com as principais ferramentas',
            'icon' => 'fas fa-plug',
        ],
    ],

    'benefits' => [
        [
            'title' => 'Confiável',
            'text' => 'Sistema estável e seguro para o seu negócio',
            'icon' => 'fas fa-shield-alt',
        ],
        [
            'title' => 'Ágil',
            'text' => 'Interface moderna e fácil de usar',
            'icon' => 'fas fa-rocket',
        ],
        [
            'title' => 'Na Nuvem',
            'text' => 'Acesse de qualquer lugar com segurança',
            'icon' => 'fas fa-cloud',
        ],
        [
            'title' => 'Suporte Especializado',
            'text' => 'Equipe pronta para te ajudar sempre',
            'icon' => 'fas fa-headset',
        ],
        [
            'title' => 'Atualizações Constantes',
            'text' => 'Novas funcionalidades regularmente',
            'icon' => 'fas fa-sync-alt',
        ],
        [
            'title' => 'Qualidade Garantida',
            'text' => 'Anos de experiência em soluções digitais',
            'icon' => 'fas fa-award',
        ],
    ],

    'plans' => [
        [
            'name' => 'Mensal',
            'price' => 'R$ 49,90',
            'period' => 'por mês · teste grátis de 3 dias',
            'highlight' => false,
            'cta' => 'Contratar com Pix',
            'url' => '/painel/checkout.php',
            'features' => [
                'Licença no sistema, módulos e site',
                'QR Code Pix automático',
                'Liberação na hora do pagamento',
                'Suporte por ticket',
            ],
        ],
        [
            'name' => 'Trimestral',
            'price' => 'R$ 134,90',
            'period' => 'a cada 3 meses · o mais escolhido',
            'highlight' => true,
            'cta' => 'Contratar com Pix',
            'url' => '/painel/checkout.php',
            'features' => [
                'Tudo do plano Mensal',
                '3 meses já inclusos',
                'Bloqueio automático no vencimento',
                'Atendimento prioritário',
            ],
        ],
        [
            'name' => 'Anual',
            'price' => 'R$ 399,00',
            'period' => 'por ano · melhor custo',
            'highlight' => false,
            'cta' => 'Contratar com Pix',
            'url' => '/painel/checkout.php',
            'features' => [
                'Tudo do plano Trimestral',
                '12 meses de licença',
                'Ideal para operação contínua',
                'Reativação automática no Pix',
            ],
        ],
    ],

    'footer_text' => 'Soluções completas para provedores, revendedores e empresas que não param.',
    'hours' => 'Segunda a Sexta, 8h às 18h',
    'phone' => '',
    'email' => '',
    'whatsapp' => '',

    'social' => [
        'facebook' => '',
        'instagram' => '',
        'youtube' => '',
    ],
];
