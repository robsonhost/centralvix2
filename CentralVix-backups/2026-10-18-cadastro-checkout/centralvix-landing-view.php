<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

function centralvix_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function centralvix_nav_items(array $urls)
{
    return [
        ['key' => 'recursos', 'href' => $urls['recursos'], 'label' => 'Recursos'],
        [
            'key' => 'catalogo',
            'href' => $urls['catalogo'] ?? '/catalogo',
            'label' => 'Serviços',
            'children' => [
                ['href' => '/hospedagem', 'label' => 'Hospedagem de Sites'],
                ['href' => '/revenda', 'label' => 'Revenda WHM/cPanel'],
                ['href' => '/vps-brasil', 'label' => 'VPS Brasil'],
                ['href' => '/vps-canada', 'label' => 'VPS Canadá'],
            ],
        ],
        ['key' => 'hospedagem', 'href' => '/hospedagem', 'label' => 'Hospedagem'],
        ['key' => 'vps', 'href' => '/vps', 'label' => 'VPS'],
        ['key' => 'revenda', 'href' => '/revenda', 'label' => 'Revenda'],
        ['key' => 'sobre', 'href' => $urls['sobre'], 'label' => 'Sobre'],
        ['key' => 'contato', 'href' => $urls['contato'], 'label' => 'Contato'],
    ];
}

function centralvix_render_header($page, array $cvix, array $urls)
{
    $brandPrefix = centralvix_h($cvix['brand_prefix'] ?? 'CENTRAL');
    $brandAccent = centralvix_h($cvix['brand_accent'] ?? 'VIX');
    $tagline = centralvix_h($cvix['tagline'] ?? 'AUTOMAÇÃO & SISTEMAS');
    $loginHref = $urls['login_page'] ?? '/login.php';
    ob_start();
    ?>
    <header class="cv-landing-header" id="cv-landing-header">
        <div class="cv-landing-wrap cv-landing-header-inner">
            <a class="cv-landing-brand" href="<?php echo centralvix_h($urls['home']); ?>" aria-label="CENTRALVIX">
                <span class="cv-landing-brand-badge" aria-hidden="true"><img src="/assets/img/logo-icon.png" alt=""></span>
                <span class="cv-landing-brand-text">
                    <span class="cv-landing-brand-name"><?php echo $brandPrefix; ?><span><?php echo $brandAccent; ?></span></span>
                    <span class="cv-landing-brand-tag"><?php echo $tagline; ?></span>
                </span>
            </a>
            <nav class="cv-landing-nav" aria-label="Principal">
                <?php foreach (centralvix_nav_items($urls) as $item) {
                    $catalogKeys = ['catalogo', 'hospedagem', 'revenda', 'revenda-hospedagem', 'vps', 'vps-brasil', 'vps-canada'];
                    $active = $page === $item['key'] || ($item['key'] === 'catalogo' && in_array($page, $catalogKeys, true)) || ($item['key'] === 'vps' && in_array($page, ['vps-brasil', 'vps-canada'], true));
                    if (!empty($item['children'])) { ?>
                    <div class="cv-mega<?php echo $active ? ' is-active' : ''; ?>">
                        <a href="<?php echo centralvix_h($item['href']); ?>"><?php echo centralvix_h($item['label']); ?><i class="fas fa-chevron-down cv-mega-caret" aria-hidden="true"></i></a>
                        <div class="cv-mega-box">
                            <?php foreach ($item['children'] as $child) { ?>
                                <a href="<?php echo centralvix_h($child['href']); ?>"><?php echo centralvix_h($child['label']); ?></a>
                            <?php } ?>
                        </div>
                    </div>
                    <?php } else { ?>
                    <a href="<?php echo centralvix_h($item['href']); ?>"<?php echo $active ? ' class="is-active"' : ''; ?>><?php echo centralvix_h($item['label']); ?></a>
                    <?php }
                } ?>
            </nav>
            <div class="cv-landing-header-actions">
                <a class="cv-landing-btn-register" href="<?php echo centralvix_h($urls['cadastro']); ?>">Criar conta</a>
                <a class="cv-landing-btn-client" href="<?php echo centralvix_h($loginHref); ?>"><i class="fas fa-lock" aria-hidden="true"></i><span>Área do Cliente</span></a>
                <button type="button" class="cv-landing-burger" id="cv-landing-burger" aria-label="Abrir menu" aria-expanded="false" aria-controls="cv-landing-mobile-panel"><i class="fas fa-bars" aria-hidden="true"></i></button>
            </div>
        </div>
    </header>
    <div class="cv-landing-mobile-panel" id="cv-landing-mobile-panel">
        <p class="cv-landing-mobile-label">Navegação</p>
        <?php foreach (centralvix_nav_items($urls) as $item) { ?>
            <a href="<?php echo centralvix_h($item['href']); ?>"><?php echo centralvix_h($item['label']); ?></a>
            <?php foreach ($item['children'] ?? [] as $child) { ?>
                <a class="cv-landing-mobile-sub" href="<?php echo centralvix_h($child['href']); ?>"><?php echo centralvix_h($child['label']); ?></a>
            <?php } ?>
        <?php } ?>
        <div class="cv-landing-mobile-actions">
            <a class="cv-landing-btn-client" href="<?php echo centralvix_h($loginHref); ?>"><i class="fas fa-lock" aria-hidden="true"></i><span>Área do Cliente</span></a>
            <a class="cv-landing-btn-register" href="<?php echo centralvix_h($urls['cadastro']); ?>">Criar conta</a>
        </div>
        <a class="cv-landing-mobile-invoice" href="<?php echo centralvix_h($urls['invoices']); ?>"><i class="fas fa-file-invoice" aria-hidden="true"></i> 2ª via de fatura</a>
    </div>
    <?php
    return ob_get_clean();
}

function centralvix_render_footer(array $cvix, array $urls, $year)
{
    $brandPrefix = centralvix_h($cvix['brand_prefix'] ?? 'CENTRAL');
    $brandAccent = centralvix_h($cvix['brand_accent'] ?? 'VIX');
    $tagline = centralvix_h($cvix['tagline'] ?? 'AUTOMAÇÃO & SISTEMAS');
    $footerText = centralvix_h($cvix['footer_text'] ?? '');
    $hours = centralvix_h($cvix['hours'] ?? '');
    $phone = trim((string) ($cvix['phone'] ?? ''));
    $email = trim((string) ($cvix['email'] ?? ''));
    $social = $cvix['social'] ?? [];
    ob_start();
    ?>
    <footer class="cv-landing-footer">
        <div class="cv-landing-wrap cv-landing-footer-grid">
            <div>
                <a class="cv-landing-brand" href="<?php echo centralvix_h($urls['home']); ?>" aria-label="CENTRALVIX">
                    <span class="cv-landing-brand-badge" aria-hidden="true"><img src="/assets/img/logo-icon.png" alt=""></span>
                    <span class="cv-landing-brand-text">
                        <span class="cv-landing-brand-name"><?php echo $brandPrefix; ?><span><?php echo $brandAccent; ?></span></span>
                        <span class="cv-landing-brand-tag"><?php echo $tagline; ?></span>
                    </span>
                </a>
                <p class="cv-landing-footer-lead"><?php echo $footerText; ?></p>
                <?php if (!empty($social['facebook']) || !empty($social['instagram']) || !empty($social['youtube'])) { ?>
                <div class="cv-landing-social">
                    <?php if (!empty($social['facebook'])) { ?><a href="<?php echo centralvix_h($social['facebook']); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><?php } ?>
                    <?php if (!empty($social['instagram'])) { ?><a href="<?php echo centralvix_h($social['instagram']); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a><?php } ?>
                    <?php if (!empty($social['youtube'])) { ?><a href="<?php echo centralvix_h($social['youtube']); ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a><?php } ?>
                </div>
                <?php } ?>
            </div>
            <div>
                <h4>NAVEGAÇÃO</h4>
                <ul>
                    <?php foreach (centralvix_nav_items($urls) as $item) { ?>
                        <li><a href="<?php echo centralvix_h($item['href']); ?>"><?php echo centralvix_h($item['label']); ?></a></li>
                    <?php } ?>
                </ul>
            </div>
            <div>
                <h4>CONTA</h4>
                <ul>
                    <li><a href="<?php echo centralvix_h($urls['login_page'] ?? '/login.php'); ?>">Entrar no painel</a></li>
                    <li><a href="<?php echo centralvix_h($urls['cadastro']); ?>">Criar conta</a></li>
                    <li><a href="<?php echo centralvix_h($urls['invoices']); ?>">2ª via de fatura</a></li>
                    <li><a href="<?php echo centralvix_h($urls['reset']); ?>">Recuperar senha</a></li>
                </ul>
            </div>
            <div>
                <h4>CONTATO</h4>
                <ul>
                    <?php if ($phone !== '') { ?><li><a href="tel:<?php echo centralvix_h(preg_replace('/\s+/', '', $phone)); ?>"><?php echo centralvix_h($phone); ?></a></li><?php } ?>
                    <?php if ($email !== '') { ?><li><a href="mailto:<?php echo centralvix_h($email); ?>"><?php echo centralvix_h($email); ?></a></li><?php } ?>
                    <?php if ($hours !== '') { ?><li><?php echo $hours; ?></li><?php } ?>
                    <li><a href="<?php echo centralvix_h($urls['contato']); ?>">Fale Conosco</a></li>
                </ul>
            </div>
            <div class="cv-footer-security">
                <h4>SEGURANÇA</h4>
                <p>Seus dados estão protegidos com criptografia e as melhores práticas de segurança.</p>
                <div class="cv-landing-seals">
                    <span class="cv-landing-seal"><i class="fas fa-lock" aria-hidden="true"></i> SSL Seguro</span>
                    <span class="cv-landing-seal"><i class="fas fa-shield-alt" aria-hidden="true"></i> Dados Protegidos</span>
                    <span class="cv-landing-seal"><i class="fas fa-headset" aria-hidden="true"></i> Suporte 24/7</span>
                </div>
                <div class="cv-landing-ssl"><i class="fas fa-check-circle" aria-hidden="true"></i> Ambiente 100% criptografado</div>
            </div>
        </div>
        <div class="cv-landing-wrap cv-landing-legal">
            <span>© <?php echo centralvix_h($year); ?> CENTRALVIX - Hospedagem &amp; Serviços Online. Todos os direitos reservados.</span>
            <span class="cv-landing-legal-note"><img src="/assets/img/logo-icon.png" alt="" class="cv-legal-logo"> Plataforma CentralVix</span>
        </div>
    </footer>
    <?php
    return ob_get_clean();
}

function centralvix_render_login_form(array $urls, $token, $captchaHtml, $loginError, $goto = 'clientarea.php')
{
    ob_start();
    ?>
    <aside class="cv-landing-card" id="cv-landing-login" aria-labelledby="cv-landing-login-title" data-cv-reveal>
        <div class="cv-landing-card-head">
            <span class="cv-landing-card-icon" aria-hidden="true"><i class="fas fa-user-astronaut"></i></span>
            <div class="cv-landing-card-headings">
                <h2 id="cv-landing-login-title">Acesse sua conta</h2>
                <p class="cv-landing-card-sub">Entre com suas credenciais para acessar o sistema</p>
            </div>
        </div>
        <?php if ($loginError) { ?>
            <div class="cv-alert cv-alert-error" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> Usuário ou senha inválidos. Tente novamente.</div>
        <?php } ?>
        <form method="post" action="<?php echo centralvix_h($urls['login']); ?>" class="login-form" id="cv-landing-login-form" role="form">
            <?php if ($token !== '') { ?><input type="hidden" name="token" value="<?php echo centralvix_h($token); ?>"><?php } ?>
            <input type="hidden" name="goto" value="<?php echo centralvix_h($goto); ?>">
            <input type="hidden" name="redirect" value="<?php echo centralvix_h($goto); ?>">
            <div class="cv-landing-field">
                <label for="inputEmail">Usuário ou E-mail</label>
                <div class="cv-landing-input">
                    <i class="fas fa-user cv-landing-input-icon" aria-hidden="true"></i>
                    <input type="text" class="form-control" name="username" id="inputEmail" placeholder="Usuário ou e-mail" autocomplete="username" autofocus>
                </div>
            </div>
            <div class="cv-landing-field">
                <label for="inputPassword">Senha</label>
                <div class="cv-landing-input">
                    <i class="fas fa-lock cv-landing-input-icon" aria-hidden="true"></i>
                    <input type="password" class="form-control pw-input" name="password" id="inputPassword" placeholder="Sua senha" autocomplete="current-password">
                    <button class="cv-landing-toggle-pw btn-reveal-pw" id="cv-landing-toggle-password" type="button" aria-label="Mostrar senha"><i class="fas fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
            <?php echo $captchaHtml; ?>
            <div class="cv-landing-row">
                <label class="cv-landing-check"><input type="checkbox" name="rememberme" value="1"> Lembrar-me</label>
                <a class="cv-landing-forgot" href="<?php echo centralvix_h($urls['reset']); ?>">Esqueci minha senha</a>
            </div>
            <button id="cv-landing-submit" type="submit" class="cv-landing-submit"><i class="fas fa-lock" aria-hidden="true"></i> Entrar na Minha Conta</button>
        </form>
        <div class="cv-landing-divider">ou</div>
        <a class="cv-landing-secondary" href="<?php echo centralvix_h($urls['invoices']); ?>"><i class="fas fa-file-invoice" aria-hidden="true"></i> Consulta de 2ª Via</a>
        <p class="cv-landing-register">Ainda não tem conta? <a href="<?php echo centralvix_h($urls['cadastro']); ?>">Cadastre-se grátis</a></p>
    </aside>
    <?php
    return ob_get_clean();
}

function centralvix_render_login_page(array $cvix, array $ctx)
{
    $urls = $ctx['urls'];
    $token = (string) ($ctx['token'] ?? '');
    $captchaHtml = (string) ($ctx['captchaHtml'] ?? '');
    $loginError = !empty($ctx['loginError']);
    $goto = (string) ($ctx['goto'] ?? 'clientarea.php');
    $brandPrefix = centralvix_h($cvix['brand_prefix'] ?? 'CENTRAL');
    $brandAccent = centralvix_h($cvix['brand_accent'] ?? 'VIX');
    $tagline = centralvix_h($cvix['tagline'] ?? 'AUTOMAÇÃO & SISTEMAS');
    $homeHref = $urls['home'] ?? '/';
    ob_start();
    ?>
    <div class="cv-auth-bg" aria-hidden="true">
        <span class="cv-auth-glow cv-auth-glow-1"></span>
        <span class="cv-auth-glow cv-auth-glow-2"></span>
        <span class="cv-auth-grid"></span>
    </div>
    <div class="cv-auth-top">
        <a class="cv-landing-brand" href="<?php echo centralvix_h($homeHref); ?>" aria-label="CENTRALVIX">
            <span class="cv-landing-brand-badge" aria-hidden="true"><img src="/assets/img/logo-icon.png" alt=""></span>
            <span class="cv-landing-brand-text">
                <span class="cv-landing-brand-name"><?php echo $brandPrefix; ?><span><?php echo $brandAccent; ?></span></span>
                <span class="cv-landing-brand-tag"><?php echo $tagline; ?></span>
            </span>
        </a>
        <a class="cv-auth-back" href="<?php echo centralvix_h($homeHref); ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Voltar ao site</a>
    </div>
    <main class="cv-auth-main">
        <section class="cv-landing-card cv-auth-card" aria-labelledby="cv-auth-title" data-cv-reveal>
            <div class="cv-landing-card-head">
                <span class="cv-landing-card-icon" aria-hidden="true"><i class="fas fa-user-astronaut"></i></span>
                <div class="cv-landing-card-headings">
                    <h1 id="cv-auth-title">Entrar na Minha Conta</h1>
                    <p class="cv-landing-card-sub">Acesse o painel para gerenciar seus serviços</p>
                </div>
            </div>
            <?php if ($loginError) { ?>
                <div class="cv-alert cv-alert-error" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i><span><strong>Não foi possível entrar.</strong> Verifique usuário e senha e tente novamente.</span></div>
            <?php } ?>
            <form method="post" action="<?php echo centralvix_h($urls['login']); ?>" class="login-form" id="cv-landing-login-form" role="form">
                <?php if ($token !== '') { ?><input type="hidden" name="token" value="<?php echo centralvix_h($token); ?>"><?php } ?>
                <input type="hidden" name="goto" value="<?php echo centralvix_h($goto); ?>">
                <input type="hidden" name="redirect" value="<?php echo centralvix_h($goto); ?>">
                <div class="cv-landing-field">
                    <label for="inputEmail">E-mail ou usuário</label>
                    <div class="cv-landing-input">
                        <i class="fas fa-user cv-landing-input-icon" aria-hidden="true"></i>
                        <input type="text" class="form-control" name="username" id="inputEmail" placeholder="Seu e-mail ou usuário" autocomplete="username" autofocus>
                    </div>
                </div>
                <div class="cv-landing-field">
                    <label for="inputPassword">Senha</label>
                    <div class="cv-landing-input">
                        <i class="fas fa-lock cv-landing-input-icon" aria-hidden="true"></i>
                        <input type="password" class="form-control pw-input" name="password" id="inputPassword" placeholder="Sua senha" autocomplete="current-password">
                        <button class="cv-landing-toggle-pw btn-reveal-pw" id="cv-landing-toggle-password" type="button" aria-label="Mostrar senha"><i class="fas fa-eye" aria-hidden="true"></i></button>
                    </div>
                </div>
                <?php echo $captchaHtml; ?>
                <div class="cv-landing-row">
                    <label class="cv-landing-check"><input type="checkbox" name="rememberme" value="1"> Lembrar-me</label>
                    <a class="cv-landing-forgot" href="<?php echo centralvix_h($urls['reset']); ?>">Esqueci minha senha</a>
                </div>
                <button id="cv-landing-submit" type="submit" class="cv-landing-submit"><i class="fas fa-lock" aria-hidden="true"></i> Entrar na Minha Conta</button>
            </form>
            <div class="cv-landing-divider">ou</div>
            <a class="cv-landing-secondary" href="<?php echo centralvix_h($urls['invoices']); ?>"><i class="fas fa-file-invoice" aria-hidden="true"></i> Consulta de 2ª Via</a>
            <p class="cv-landing-register">Ainda não tem conta? <a href="<?php echo centralvix_h($urls['cadastro']); ?>">Cadastre-se grátis</a></p>
        </section>
        <p class="cv-auth-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> Conexão protegida. A autenticação segue as regras de segurança do sistema.</p>
    </main>
    <?php
    return ob_get_clean();
}

function centralvix_page_hero($kicker, $title, $text)
{
    ob_start();
    ?>
    <section class="cv-page-hero">
        <div class="cv-landing-wrap">
            <p class="cv-landing-badge"><?php echo centralvix_h($kicker); ?></p>
            <h1><?php echo centralvix_h($title); ?></h1>
            <p class="cv-page-lead"><?php echo centralvix_h($text); ?></p>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_home(array $cvix, array $ctx)
{
    $urls = $ctx['urls'];
    $titleBefore = centralvix_h($cvix['hero_title_before'] ?? 'Tecnologia e automação para');
    $titleAfter = centralvix_h($cvix['hero_title_after'] ?? 'o seu negócio');
    $titleHighlight = centralvix_h($cvix['hero_title_highlight'] ?? 'crescer');
    $lead = centralvix_h($cvix['hero_text'] ?? '');
    $badge = centralvix_h($cvix['badge'] ?? 'SISTEMA CENTRALVIX');
    $loginPage = $urls['login_page'] ?? '/login.php';
    $aboutKicker = centralvix_h($cvix['home_about_kicker'] ?? 'A CENTRALVIX');
    $aboutTitle = centralvix_h($cvix['home_about_title'] ?? 'Automação, controle e suporte em um só painel');
    $aboutLead = centralvix_h($cvix['about_lead'] ?? '');
    $aboutText = centralvix_h($cvix['about_text'] ?? '');
    $heroPoints = $cvix['home_about_points'] ?? [];
    $serviceIcons = [
        'hospedagem' => 'fas fa-cloud',
        'revenda' => 'fas fa-layer-group',
        'vps' => 'fas fa-microchip',
        'vps-brasil' => 'fas fa-map-location-dot',
        'vps-canada' => 'fas fa-server',
        'streaming' => 'fas fa-headphones',
        'revenda-streaming' => 'fas fa-tower-broadcast',
        'web-radio' => 'fas fa-radio',
        'aplicativos' => 'fas fa-mobile-screen-button',
        'sites' => 'fas fa-globe',
        'desenvolvimento' => 'fas fa-code',
    ];
    $skipServices = ['catalogo' => true, 'revenda-hospedagem' => true];
    $services = [];
    foreach (($ctx['catalog']['nav'] ?? []) as $slug => $meta) {
        if (isset($skipServices[$slug])) {
            continue;
        }
        $services[] = [
            'href' => '/' . $slug,
            'title' => (string) ($meta['title'] ?? $slug),
            'lead' => (string) ($meta['lead'] ?? ''),
            'icon' => $serviceIcons[$slug] ?? 'fas fa-bolt',
        ];
    }
    ob_start();
    ?>
    <section class="cv-landing-hero">
        <div class="cv-landing-hero-bg" aria-hidden="true">
            <span class="cv-landing-hero-glow cv-landing-hero-glow-1"></span>
            <span class="cv-landing-hero-glow cv-landing-hero-glow-2"></span>
            <span class="cv-landing-hero-grid"></span>
        </div>
        <div class="cv-landing-wrap cv-landing-hero-inner">
            <div class="cv-landing-intro" data-cv-reveal>
                <span class="cv-landing-badge"><i class="fas fa-shield-halved" aria-hidden="true"></i> <?php echo $badge; ?></span>
                <h1 class="cv-landing-title"><?php echo $titleBefore; ?><br><?php echo $titleAfter; ?> <em><?php echo $titleHighlight; ?></em></h1>
                <p class="cv-landing-lead"><?php echo $lead; ?></p>
                <div class="cv-landing-hero-actions">
                    <a href="<?php echo centralvix_h($loginPage); ?>" class="cv-btn-primary">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        <span>
                            <strong>Área do Cliente</strong>
                            <small>Acesse sua conta</small>
                        </span>
                    </a>
                    <a href="<?php echo centralvix_h($urls['cadastro']); ?>" class="cv-btn-secondary">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        <span>
                            <strong>Criar Minha Conta</strong>
                            <small>Cadastro rápido e gratuito</small>
                        </span>
                    </a>
                </div>
                <div class="cv-hero-panel" data-cv-reveal aria-hidden="true">
                    <div class="cv-hero-panel-chrome">
                        <span class="cv-hero-panel-dot"></span>
                        <span class="cv-hero-panel-dot"></span>
                        <span class="cv-hero-panel-dot"></span>
                        <span class="cv-hero-panel-url"><i class="fas fa-lock"></i> painel.centralvix.com.br</span>
                    </div>
                    <div class="cv-hero-panel-body">
                        <div class="cv-hero-panel-side">
                            <span class="cv-hero-panel-logo"><img src="/assets/img/logo-icon.png" alt=""></span>
                            <span class="cv-hero-panel-nav is-on"><i class="fas fa-chart-line"></i></span>
                            <span class="cv-hero-panel-nav"><i class="fas fa-server"></i></span>
                            <span class="cv-hero-panel-nav"><i class="fas fa-file-invoice"></i></span>
                            <span class="cv-hero-panel-nav"><i class="fas fa-headset"></i></span>
                        </div>
                        <div class="cv-hero-panel-content">
                            <div class="cv-hero-panel-head">
                                <span class="cv-hero-panel-heading">Painel do Cliente</span>
                                <span class="cv-hero-panel-status"><i class="fas fa-circle"></i> Conectado</span>
                            </div>
                            <div class="cv-hero-panel-cards">
                                <div class="cv-hero-panel-card"><small>Serviços contratados</small><span class="cv-hero-panel-bar"></span></div>
                                <div class="cv-hero-panel-card"><small>Faturas emitidas</small><span class="cv-hero-panel-bar"></span></div>
                                <div class="cv-hero-panel-card"><small>Tickets respondidos</small><span class="cv-hero-panel-bar"></span></div>
                            </div>
                            <div class="cv-hero-panel-chart">
                                <span style="--cv-h:38%"></span>
                                <span style="--cv-h:52%"></span>
                                <span style="--cv-h:44%"></span>
                                <span style="--cv-h:63%"></span>
                                <span style="--cv-h:49%"></span>
                                <span style="--cv-h:71%"></span>
                                <span style="--cv-h:58%"></span>
                                <span style="--cv-h:79%"></span>
                                <span style="--cv-h:66%"></span>
                                <span style="--cv-h:88%"></span>
                                <span style="--cv-h:60%"></span>
                                <span style="--cv-h:74%"></span>
                                <span style="--cv-h:54%"></span>
                                <span style="--cv-h:84%"></span>
                            </div>
                        </div>
                        <div class="cv-hero-panel-sideinfo">
                            <div class="cv-hero-panel-row">
                                <span class="cv-hero-panel-ic"><i class="fas fa-cloud"></i></span>
                                <div><strong>Hospedagem de Sites</strong><small>Serviço ativo</small></div>
                                <span class="cv-hero-panel-tag">Ativo</span>
                            </div>
                            <div class="cv-hero-panel-row">
                                <span class="cv-hero-panel-ic"><i class="fas fa-microchip"></i></span>
                                <div><strong>VPS Brasil</strong><small>Serviço ativo</small></div>
                                <span class="cv-hero-panel-tag">Ativo</span>
                            </div>
                            <div class="cv-hero-panel-row">
                                <span class="cv-hero-panel-ic"><i class="fas fa-file-invoice"></i></span>
                                <div><strong>Segunda via</strong><small>Disponível no painel</small></div>
                                <span class="cv-hero-panel-tag is-ok">Emitida</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="cv-landing-wrap cv-landing-hero-strip">
            <div class="cv-landing-features" data-cv-reveal>
                <?php foreach ($heroPoints as $point) { ?>
                    <div class="cv-landing-feature">
                        <span class="cv-landing-feature-icon"><i class="<?php echo centralvix_h($point['icon'] ?? 'fas fa-check'); ?>" aria-hidden="true"></i></span>
                        <div class="cv-landing-feature-body"><span><?php echo centralvix_h($point['text'] ?? ''); ?></span></div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>
    <section class="cv-landing-about">
        <div class="cv-landing-wrap cv-about-grid">
            <div class="cv-about-copy" data-cv-reveal>
                <p class="cv-landing-why-kicker"><?php echo $aboutKicker; ?></p>
                <h2><?php echo $aboutTitle; ?></h2>
                <p class="cv-landing-why-lead"><?php echo $aboutLead; ?></p>
                <div class="cv-page-actions">
                    <a class="cv-btn-secondary" href="<?php echo centralvix_h($urls['sobre']); ?>">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        <span><strong>Conhecer a CENTRALVIX</strong></span>
                    </a>
                </div>
            </div>
            <div class="cv-about-panel" data-cv-reveal>
                <span class="cv-about-panel-icon" aria-hidden="true"><img src="/assets/img/logo-icon.png" alt=""></span>
                <p><?php echo $aboutText; ?></p>
                <ul class="cv-about-mini">
                    <li><i class="fas fa-check" aria-hidden="true"></i> Painel único para todos os seus serviços</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i> Contratação direta, sem burocracia</li>
                    <li><i class="fas fa-check" aria-hidden="true"></i> Atendimento em português</li>
                </ul>
            </div>
        </div>
    </section>
    <section class="cv-landing-services">
        <div class="cv-landing-wrap">
            <div class="cv-landing-why-head" data-cv-reveal>
                <p class="cv-landing-why-kicker">SERVIÇOS</p>
                <h2>Soluções completas em um só painel</h2>
                <p class="cv-landing-why-lead">Contratação direta pelo sistema, ativação ágil e gestão centralizada de tudo que você contrata.</p>
            </div>
            <div class="cv-services-grid">
                <?php foreach ($services as $svc) { ?>
                    <a class="cv-service-card" href="<?php echo centralvix_h($svc['href']); ?>" data-cv-reveal>
                        <span class="cv-service-icon"><i class="<?php echo centralvix_h($svc['icon']); ?>" aria-hidden="true"></i></span>
                        <h3><?php echo centralvix_h($svc['title']); ?></h3>
                        <p><?php echo centralvix_h($svc['lead']); ?></p>
                        <span class="cv-service-more">Saiba mais <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                    </a>
                <?php } ?>
                <a class="cv-service-card cv-service-all" href="/catalogo" data-cv-reveal>
                    <span class="cv-service-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></span>
                    <h3>Catálogo completo</h3>
                    <p>Compare todos os serviços e preços em um só lugar.</p>
                    <span class="cv-service-more">Abrir catálogo <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
                </a>
            </div>
        </div>
    </section>
    <section class="cv-landing-why">
        <div class="cv-landing-wrap">
            <div class="cv-landing-why-head" data-cv-reveal>
                <p class="cv-landing-why-kicker">BENEFÍCIOS</p>
                <h2>Tudo que você precisa em uma única plataforma</h2>
                <p class="cv-landing-why-lead">Infraestrutura estável, painel intuitivo e suporte que resolve — seus serviços no ar, sem preocupação.</p>
            </div>
            <div class="cv-landing-why-grid">
                <?php foreach (($cvix['benefits'] ?? []) as $item) { ?>
                    <article class="cv-landing-why-card" data-cv-reveal>
                        <div class="cv-landing-why-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-circle'); ?>" aria-hidden="true"></i></div>
                        <h3><?php echo centralvix_h($item['title'] ?? ''); ?></h3>
                        <p><?php echo centralvix_h($item['text'] ?? ''); ?></p>
                    </article>
                <?php } ?>
            </div>
        </div>
    </section>
    <section class="cv-landing-diff">
        <div class="cv-landing-wrap cv-diff-grid">
            <div class="cv-diff-copy" data-cv-reveal>
                <p class="cv-landing-why-kicker">DIFERENCIAIS</p>
                <h2>Tecnologia que trabalha por você</h2>
                <p class="cv-landing-why-lead">Faturas automáticas, ativação dos serviços e suporte integrado — previsibilidade do pedido à entrega.</p>
                <div class="cv-page-actions">
                    <a class="cv-btn-primary" href="<?php echo centralvix_h($urls['recursos']); ?>">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                        <span><strong>Conhecer os recursos</strong></span>
                    </a>
                </div>
            </div>
            <div class="cv-diff-list">
                <?php foreach (($cvix['differentials'] ?? []) as $item) { ?>
                    <article class="cv-diff-item" data-cv-reveal>
                        <span class="cv-landing-why-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-circle'); ?>" aria-hidden="true"></i></span>
                        <div>
                            <h3><?php echo centralvix_h($item['title'] ?? ''); ?></h3>
                            <p><?php echo centralvix_h($item['text'] ?? ''); ?></p>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </div>
    </section>
    <section class="cv-landing-trust">
        <div class="cv-landing-wrap">
            <div class="cv-landing-why-head" data-cv-reveal>
                <p class="cv-landing-why-kicker">SEGURANÇA E CONFIANÇA</p>
                <h2>Proteção em cada etapa</h2>
                <p class="cv-landing-why-lead">Boas práticas de segurança aplicadas na plataforma e no atendimento.</p>
            </div>
            <div class="cv-trust-grid">
                <?php foreach (($cvix['trust'] ?? []) as $item) { ?>
                    <article class="cv-trust-card" data-cv-reveal>
                        <div class="cv-landing-why-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-circle'); ?>" aria-hidden="true"></i></div>
                        <h3><?php echo centralvix_h($item['title'] ?? ''); ?></h3>
                        <p><?php echo centralvix_h($item['text'] ?? ''); ?></p>
                    </article>
                <?php } ?>
            </div>
        </div>
    </section>
    <section class="cv-landing-cta">
        <div class="cv-landing-wrap">
            <div class="cv-cta-content" data-cv-reveal>
                <span class="cv-cta-badge"><i class="fas fa-sparkles" aria-hidden="true"></i> COMECE EM MINUTOS</span>
                <h2>Pronto para começar?</h2>
                <p>Crie sua conta agora e gerencie hospedagem, VPS, streaming e web rádio — faturas, notificações e suporte em um único painel.</p>
                <div class="cv-cta-actions">
                    <a href="<?php echo centralvix_h($urls['cadastro']); ?>" class="cv-btn-light cv-btn-large">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>
                        Criar Minha Conta
                    </a>
                    <a href="<?php echo centralvix_h($loginPage); ?>" class="cv-btn-ghost cv-btn-large">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        Área do Cliente
                    </a>
                </div>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_recursos(array $cvix)
{
    ob_start();
    echo centralvix_page_hero('RECURSOS', 'Tudo no mesmo painel', 'Automação, relatórios, segurança e integrações para operar com previsibilidade.');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap cv-page-grid">
            <?php foreach (($cvix['differentials'] ?? []) as $item) { ?>
                <article class="cv-page-card">
                    <div class="cv-landing-why-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-circle'); ?>"></i></div>
                    <h2><?php echo centralvix_h($item['title'] ?? ''); ?></h2>
                    <p><?php echo centralvix_h($item['text'] ?? ''); ?></p>
                </article>
            <?php } ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_beneficios(array $cvix)
{
    ob_start();
    echo centralvix_page_hero('BENEFÍCIOS', 'Por que escolher a CENTRALVIX', 'Uma plataforma completa, estável e simples de usar no dia a dia.');
    ?>
    <section class="cv-page-body cv-page-body-light">
        <div class="cv-landing-wrap cv-landing-why-grid">
            <?php foreach (($cvix['benefits'] ?? []) as $item) { ?>
                <article class="cv-landing-why-card">
                    <div class="cv-landing-why-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-circle'); ?>"></i></div>
                    <h3><?php echo centralvix_h($item['title'] ?? ''); ?></h3>
                    <p><?php echo centralvix_h($item['text'] ?? ''); ?></p>
                </article>
            <?php } ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_sobre(array $cvix, array $urls)
{
    ob_start();
    echo centralvix_page_hero('SOBRE', 'Sua central de serviços online', $cvix['about_lead'] ?? '');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap cv-prose">
            <p><?php echo centralvix_h($cvix['about_text'] ?? ''); ?></p>
            <div class="cv-page-actions">
                <a class="cv-landing-submit" href="<?php echo centralvix_h($urls['cadastro']); ?>">Criar conta</a>
                <a class="cv-landing-secondary" href="<?php echo centralvix_h($urls['contato']); ?>">Falar com a equipe</a>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_planos(array $cvix, array $ctx)
{
    $urls = $ctx['urls'];
    $products = $ctx['products'] ?? [];
    $plans = $cvix['plans'] ?? [];
    ob_start();
    echo centralvix_page_hero('PLANOS', 'Escolha o plano certo para crescer', 'Teste grátis de 3 dias. Depois pague no Pix e a licença libera na hora. No vencimento, o módulo é suspenso automaticamente.');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap">
            <div class="cv-plans-grid">
                <?php foreach ($plans as $plan) { ?>
                    <article class="cv-plan-card<?php echo !empty($plan['highlight']) ? ' is-featured' : ''; ?>">
                        <?php if (!empty($plan['highlight'])) { ?><span class="cv-plan-badge">Mais escolhido</span><?php } ?>
                        <h2><?php echo centralvix_h($plan['name'] ?? ''); ?></h2>
                        <p class="cv-plan-price"><?php echo centralvix_h($plan['price'] ?? ''); ?></p>
                        <p class="cv-plan-period"><?php echo centralvix_h($plan['period'] ?? ''); ?></p>
                        <ul>
                            <?php foreach (($plan['features'] ?? []) as $feature) { ?>
                                <li><i class="fas fa-check" aria-hidden="true"></i> <?php echo centralvix_h($feature); ?></li>
                            <?php } ?>
                        </ul>
                        <a class="cv-landing-submit" href="<?php echo centralvix_h($plan['url'] ?? ($urls['checkout'] ?? '/painel/checkout.php')); ?>"><?php echo centralvix_h($plan['cta'] ?? 'Contratar com Pix'); ?></a>
                    </article>
                <?php } ?>
            </div>
            <p class="cv-plan-period" style="text-align:center;margin:22px 0 0">Já usou o teste? Acesse o checkout com a sua chave para reativar.</p>
            <?php
            $whmcsProducts = $ctx['catalog']['products'] ?? [];
            if ($whmcsProducts) {
                echo '<h3 class="cv-catalog-subtitle">Hospedagem e serviços do WHMCS</h3>';
                echo centralvix_render_catalog_cards($whmcsProducts);
            } elseif ($products) {
                echo '<h3 class="cv-catalog-subtitle">Hospedagem e serviços</h3><div class="cv-plans-grid">';
                foreach ($products as $product) {
                    echo '<article class="cv-plan-card"><h2>' . centralvix_h($product['name']) . '</h2>';
                    if (!empty($product['description'])) {
                        $desc = $product['description'];
                        if (strlen($desc) > 160) {
                            $desc = substr($desc, 0, 157) . '...';
                        }
                        echo '<p>' . centralvix_h($desc) . '</p>';
                    }
                    echo '<a class="cv-landing-submit" href="' . centralvix_h($urls['cart'] . '?a=add&pid=' . (int) $product['id']) . '">Contratar</a></article>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_contato(array $cvix, array $ctx)
{
    $urls = $ctx['urls'];
    $token = $ctx['token'];
    ob_start();
    echo centralvix_page_hero('CONTATO', 'Fale com a CENTRALVIX', 'Envie sua mensagem. Retornamos no horário comercial.');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap cv-form-wrap">
            <?php if (!empty($ctx['contactSent'])) { ?>
                <div class="cv-alert cv-alert-ok" role="status">Mensagem enviada. Em breve retornamos o contato.</div>
            <?php } else { ?>
                <?php if (!empty($ctx['contactError'])) { ?>
                    <div class="cv-alert cv-alert-error" role="alert"><?php echo centralvix_h($ctx['contactError']); ?></div>
                <?php } ?>
                <form method="post" action="<?php echo centralvix_h($urls['contato']); ?>" class="cv-form-card">
                    <input type="hidden" name="cvix_contact" value="1">
                    <?php if ($token !== '') { ?><input type="hidden" name="token" value="<?php echo centralvix_h($token); ?>"><?php } ?>
                    <div class="cv-form-grid">
                        <div class="cv-landing-field">
                            <label for="contactName">Nome</label>
                            <input type="text" id="contactName" name="name" required>
                        </div>
                        <div class="cv-landing-field">
                            <label for="contactEmail">E-mail</label>
                            <input type="email" id="contactEmail" name="email" required>
                        </div>
                    </div>
                    <div class="cv-landing-field">
                        <label for="contactSubject">Assunto</label>
                        <input type="text" id="contactSubject" name="subject" required>
                    </div>
                    <div class="cv-landing-field">
                        <label for="contactMessage">Mensagem</label>
                        <textarea id="contactMessage" name="message" rows="6" required></textarea>
                    </div>
                    <?php echo $ctx['captchaHtml']; ?>
                    <button type="submit" class="cv-landing-submit">Enviar mensagem</button>
                </form>
            <?php } ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_custom_field($field)
{
    $id = (int) ($field->id ?? 0);
    $name = (string) ($field->fieldname ?? 'Campo');
    $type = strtolower((string) ($field->fieldtype ?? 'text'));
    $required = in_array((string) ($field->required ?? ''), ['on', '1'], true);
    $options = array_filter(array_map('trim', explode(',', (string) ($field->fieldoptions ?? ''))));
    $inputName = 'customfield[' . $id . ']';
    $label = centralvix_h($name) . ($required ? ' *' : '');
    ob_start();
    echo '<div class="cv-landing-field"><label for="cf' . $id . '">' . $label . '</label>';
    if ($type === 'dropdown' && $options) {
        echo '<select id="cf' . $id . '" name="' . $inputName . '"' . ($required ? ' required' : '') . '><option value="">Selecione</option>';
        foreach ($options as $opt) {
            echo '<option value="' . centralvix_h($opt) . '">' . centralvix_h($opt) . '</option>';
        }
        echo '</select>';
    } elseif ($type === 'tickbox') {
        echo '<label class="cv-landing-check"><input type="checkbox" id="cf' . $id . '" name="' . $inputName . '" value="1"> ' . centralvix_h($name) . '</label>';
    } elseif ($type === 'textarea') {
        echo '<textarea id="cf' . $id . '" name="' . $inputName . '" rows="3"' . ($required ? ' required' : '') . '></textarea>';
    } else {
        echo '<input type="text" id="cf' . $id . '" name="' . $inputName . '"' . ($required ? ' required' : '') . '>';
    }
    echo '</div>';
    return ob_get_clean();
}

function centralvix_render_cadastro(array $ctx)
{
    $urls = $ctx['urls'];
    $token = $ctx['token'];
    $cvix = $ctx['cvix'] ?? [];
    $loginPage = $urls['login_page'] ?? '/login.php';
    $panelPoints = array_slice($cvix['benefits'] ?? [], 0, 4);
    $states = [
        'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia',
        'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás',
        'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
        'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo',
        'SE' => 'Sergipe', 'TO' => 'Tocantins',
    ];
    ob_start();
    echo centralvix_page_hero('CRIAR CONTA', 'Crie sua conta CENTRALVIX', 'Preencha seus dados. Depois do cadastro, você acessa o painel com o mesmo e-mail e senha.');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap cv-reg-grid">
            <aside class="cv-reg-panel" data-cv-reveal>
                <p class="cv-landing-why-kicker">BEM-VINDO À CENTRALVIX</p>
                <h2>Sua conta em poucos passos</h2>
                <p>Com ela você contrata serviços, emite segunda via e acompanha tudo em um único painel.</p>
                <ul class="cv-reg-points">
                    <?php foreach ($panelPoints as $item) { ?>
                        <li>
                            <span class="cv-reg-point-icon"><i class="<?php echo centralvix_h($item['icon'] ?? 'fas fa-check'); ?>" aria-hidden="true"></i></span>
                            <span><strong><?php echo centralvix_h($item['title'] ?? ''); ?></strong><?php echo centralvix_h($item['text'] ?? ''); ?></span>
                        </li>
                    <?php } ?>
                </ul>
                <div class="cv-reg-signin">
                    <span>Já tem uma conta?</span>
                    <a href="<?php echo centralvix_h($loginPage); ?>">Entrar na Minha Conta</a>
                </div>
            </aside>
            <form method="post" action="<?php echo centralvix_h($urls['register']); ?>" class="cv-form-card cv-reg-form" id="cv-register-form">
                <input type="hidden" name="register" value="true">
                <?php if ($token !== '') { ?><input type="hidden" name="token" value="<?php echo centralvix_h($token); ?>"><?php } ?>
                <input type="hidden" name="country" value="BR">
                <div class="cv-form-section">
                    <h3 class="cv-form-section-title">Dados pessoais</h3>
                    <div class="cv-form-grid">
                        <div class="cv-landing-field">
                            <label for="inputFirstName">Nome <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="text" name="firstname" id="inputFirstName" required autocomplete="given-name">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputLastName">Sobrenome <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="text" name="lastname" id="inputLastName" required autocomplete="family-name">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputRegEmail">E-mail <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="email" name="email" id="inputRegEmail" required autocomplete="email">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputPhone">Telefone <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="tel" name="phonenumber" id="inputPhone" required autocomplete="tel">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputCompanyName">Empresa</label>
                            <input type="text" name="companyname" id="inputCompanyName" autocomplete="organization">
                        </div>
                        <?php
                        $hasCpfField = false;
                        foreach (($ctx['customFields'] ?? []) as $cf) {
                            if (stripos((string) ($cf->fieldname ?? ''), 'cpf') !== false || stripos((string) ($cf->fieldname ?? ''), 'cnpj') !== false) {
                                $hasCpfField = true;
                                break;
                            }
                        }
                        if (!$hasCpfField) { ?>
                        <div class="cv-landing-field">
                            <label for="inputTaxId">CPF ou CNPJ</label>
                            <input type="text" name="tax_id" id="inputTaxId" inputmode="numeric">
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="cv-form-section">
                    <h3 class="cv-form-section-title">Endereço</h3>
                    <div class="cv-form-grid">
                        <div class="cv-landing-field cv-span-2">
                            <label for="inputAddress1">Endereço <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="text" name="address1" id="inputAddress1" required autocomplete="address-line1">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputCity">Cidade <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="text" name="city" id="inputCity" required autocomplete="address-level2">
                        </div>
                        <div class="cv-landing-field">
                            <label for="state">Estado <span class="cv-req" aria-hidden="true">*</span></label>
                            <select name="state" id="state" required>
                                <option value="">Selecione</option>
                                <?php foreach ($states as $uf => $label) { ?>
                                    <option value="<?php echo centralvix_h($uf); ?>"><?php echo centralvix_h($label); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputPostcode">CEP <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="text" name="postcode" id="inputPostcode" required autocomplete="postal-code">
                        </div>
                    </div>
                </div>
                <div class="cv-form-section">
                    <h3 class="cv-form-section-title">Acesso e segurança</h3>
                    <div class="cv-form-grid">
                        <div class="cv-landing-field">
                            <label for="inputNewPassword1">Senha <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="password" name="password" id="inputNewPassword1" required autocomplete="new-password">
                        </div>
                        <div class="cv-landing-field">
                            <label for="inputNewPassword2">Confirmar senha <span class="cv-req" aria-hidden="true">*</span></label>
                            <input type="password" name="password2" id="inputNewPassword2" required autocomplete="new-password">
                        </div>
                    </div>
                </div>
                <?php if (!empty($ctx['customFields'])) { ?>
                <div class="cv-form-section">
                    <h3 class="cv-form-section-title">Informações adicionais</h3>
                    <?php foreach ($ctx['customFields'] as $field) { echo centralvix_render_custom_field($field); } ?>
                </div>
                <?php } ?>
                <?php echo $ctx['captchaHtml']; ?>
                <label class="cv-landing-check cv-tos">
                    <input type="checkbox" name="accepttos" value="1" required>
                    Li e aceito os termos de serviço
                </label>
                <button type="submit" class="cv-landing-submit"><i class="fas fa-user-plus" aria-hidden="true"></i> Criar Minha Conta</button>
                <p class="cv-landing-register">Já tem conta? <a href="<?php echo centralvix_h($loginPage); ?>">Entrar na Minha Conta</a></p>
            </form>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_2via(array $ctx)
{
    $urls = $ctx['urls'];
    ob_start();
    echo centralvix_page_hero('2ª VIA', 'Consulta de 2ª via', 'Informe o número da fatura que chegou no e-mail de cobrança, ou entre na sua conta para ver todas.');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap cv-split">
            <form class="cv-form-card" method="get" action="/viewinvoice.php">
                <h2>Consultar fatura</h2>
                <p class="cv-landing-card-sub">O número aparece no e-mail de cobrança, em geral como INV ou #.</p>
                <div class="cv-landing-field">
                    <label for="invoiceId">Número da fatura</label>
                    <input type="text" id="invoiceId" name="id" required inputmode="numeric" placeholder="Ex.: 1024">
                </div>
                <button type="submit" class="cv-landing-submit"><i class="fas fa-file-invoice" aria-hidden="true"></i> Abrir 2ª via</button>
            </form>
            <?php echo centralvix_render_login_form($urls, $ctx['token'], $ctx['captchaHtml'], $ctx['loginError'], 'clientarea.php?action=invoices'); ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_catalog_nav(array $pages)
{
    $skip = ['catalogo' => true, 'revenda-hospedagem' => true];
    ob_start();
    ?>
    <nav class="cv-catalog-nav" aria-label="Serviços">
        <a href="/catalogo">Todos</a>
        <?php foreach ($pages as $slug => $meta) {
            if (isset($skip[$slug])) {
                continue;
            } ?>
            <a href="<?php echo centralvix_h('/' . $slug); ?>"><?php echo centralvix_h($meta['title']); ?></a>
        <?php } ?>
    </nav>
    <?php
    return ob_get_clean();
}

function centralvix_render_catalog_cards(array $products)
{
    if (!$products) {
        return '<p class="cv-plan-period">Nenhum produto público neste segmento ainda. Os cards aparecem quando o produto existir no WHMCS e estiver visível.</p>';
    }
    $hasAnnual = false;
    $hasMonthly = false;
    foreach ($products as $p) {
        if ((float) ($p['monthly'] ?? 0) > 0) {
            $hasMonthly = true;
        }
        if ((float) ($p['annually'] ?? 0) > 0) {
            $hasAnnual = true;
        }
    }
    ob_start();
    if ($hasMonthly && $hasAnnual) {
        echo '<div class="cv-cycle-toggle" data-cycle-root><button type="button" class="is-on" data-cycle="monthly">Mensal</button><button type="button" data-cycle="annually">Anual</button></div>';
    }
    echo '<div class="cv-plans-grid cv-catalog-grid">';
    foreach ($products as $p) {
        $monthly = centralvix_catalog_money($p['monthly'] ?? null);
        $annually = centralvix_catalog_money($p['annually'] ?? null);
        $compareM = !empty($p['compare_monthly']) && (float) $p['compare_monthly'] > (float) ($p['monthly'] ?? 0)
            ? centralvix_catalog_money($p['compare_monthly']) : null;
        $pid = (int) $p['id'];
        $url = '/cart.php?a=add&pid=' . $pid;
        $badge = trim((string) ($p['badge'] ?? ''));
        $featured = $badge !== '';
        $loc = (string) ($p['location'] ?? $p['group_segment'] ?? '');
        echo '<article class="cv-plan-card' . ($featured ? ' is-featured' : '') . '" data-monthly="' . centralvix_h($monthly ?: '') . '" data-annually="' . centralvix_h($annually ?: '') . '" data-location="' . centralvix_h($loc) . '">';
        if ($badge !== '') {
            echo '<span class="cv-plan-badge">' . centralvix_h($badge) . '</span>';
        }
        if (!empty($p['promotion_label'])) {
            echo '<span class="cv-plan-off">' . centralvix_h($p['promotion_label']) . '</span>';
        }
        echo '<h2>' . centralvix_h($p['name']) . '</h2>';
        if (!empty($p['slogan'])) {
            echo '<p class="cv-plan-period">' . centralvix_h($p['slogan']) . '</p>';
        }
        echo '<p class="cv-plan-from">a partir de</p>';
        if ($compareM) {
            echo '<p class="cv-plan-compare">' . centralvix_h($compareM) . '</p>';
        }
        echo '<p class="cv-plan-price" data-price>' . centralvix_h($monthly ?: ($annually ?: 'Sob consulta')) . '</p>';
        echo '<p class="cv-plan-period" data-period>' . ($monthly ? '/mês' : ($annually ? '/ano' : '')) . '</p>';
        echo '<ul>';
        foreach (($p['features'] ?? []) as $feat) {
            $label = trim(implode(' ', array_filter([
                (string) ($feat['value'] ?? ''),
                (string) ($feat['unit'] ?? ''),
                (string) ($feat['name'] ?? ''),
            ])));
            if ($label === '') {
                continue;
            }
            echo '<li><i class="fas fa-check" aria-hidden="true"></i> ' . centralvix_h($label) . '</li>';
        }
        echo '</ul>';
        echo '<a class="cv-landing-submit" href="' . centralvix_h($url) . '">Contratar agora</a>';
        echo '</article>';
    }
    echo '</div>';
    return ob_get_clean();
}

function centralvix_render_catalog_page(array $ctx)
{
    $catalog = $ctx['catalog'] ?? ['page' => ['title' => 'Catálogo', 'headline' => 'Serviços', 'lead' => ''], 'products' => [], 'groups' => [], 'nav' => []];
    $page = $catalog['page'];
    ob_start();
    echo centralvix_page_hero(strtoupper($page['title'] ?? 'CATÁLOGO'), $page['headline'] ?? 'Serviços CentralVix', $page['lead'] ?? '');
    ?>
    <section class="cv-page-body">
        <div class="cv-landing-wrap">
            <?php echo centralvix_render_catalog_nav($catalog['nav'] ?? []); ?>
            <?php if (!empty($page['locations'])) { ?>
                <div class="cv-loc-toggle" data-loc-root>
                    <button type="button" class="is-on" data-loc="brasil">🇧🇷 Servidores Brasil · PING BAIXO</button>
                    <button type="button" data-loc="canada">🇨🇦 Servidores Canadá · INFRAESTRUTURA GLOBAL</button>
                </div>
            <?php } ?>
            <?php
            if (!empty($catalog['groups'])) {
                foreach ($catalog['groups'] as $group) {
                    echo '<div class="cv-catalog-group" data-group-location="' . centralvix_h($group['slug'] ?? '') . '">';
                    echo '<h3 class="cv-catalog-subtitle">' . centralvix_h($group['name']) . '</h3>';
                    if (!empty($group['tagline'])) {
                        echo '<p class="cv-plan-period">' . centralvix_h($group['tagline']) . '</p>';
                    }
                    echo centralvix_render_catalog_cards($group['products']);
                    echo '</div>';
                }
            } else {
                echo centralvix_render_catalog_cards($catalog['products'] ?? []);
            }
            ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function centralvix_render_public_page($page, array $cvix, array $ctx)
{
    $urls = $ctx['urls'];
    $year = $ctx['year'] ?? date('Y');
    if ($page === 'login') {
        return '<div class="cv-landing cv-landing-auth" id="cv-landing-root">'
            . centralvix_render_login_page($cvix, $ctx)
            . '</div>';
    }
    $map = [
        'home' => static function () use ($cvix, $ctx) { return centralvix_render_home($cvix, $ctx); },
        'recursos' => static function () use ($cvix) { return centralvix_render_recursos($cvix); },
        'planos' => static function () use ($cvix, $ctx) { return centralvix_render_planos($cvix, $ctx); },
        'catalogo' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'hospedagem' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'revenda' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'revenda-hospedagem' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'vps' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'vps-brasil' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'vps-canada' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'streaming' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'revenda-streaming' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'web-radio' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'aplicativos' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'sites' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'desenvolvimento' => static function () use ($ctx) { return centralvix_render_catalog_page($ctx); },
        'beneficios' => static function () use ($cvix) { return centralvix_render_beneficios($cvix); },
        'sobre' => static function () use ($cvix, $ctx) { return centralvix_render_sobre($cvix, $ctx['urls']); },
        'contato' => static function () use ($cvix, $ctx) { return centralvix_render_contato($cvix, $ctx); },
        'cadastro' => static function () use ($ctx) { return centralvix_render_cadastro($ctx); },
        '2via' => static function () use ($ctx) { return centralvix_render_2via($ctx); },
    ];
    $body = isset($map[$page]) ? $map[$page]() : $map['home']();
    return '<div class="cv-landing" id="cv-landing-root">'
        . centralvix_render_header($page, $cvix, $urls)
        . '<main class="cv-landing-main">'
        . $body
        . '</main>'
        . centralvix_render_footer($cvix, $urls, $year)
        . '</div>';
}

function centralvix_render_public_landing(array $cvix, array $urls, $token, $captchaHtml, $captchaBtnClass, $year)
{
    return centralvix_render_public_page('login', $cvix, [
        'urls' => $urls,
        'token' => $token,
        'captchaHtml' => $captchaHtml,
        'year' => $year,
        'loginError' => false,
        'goto' => 'clientarea.php',
        'customFields' => [],
        'products' => [],
        'catalog' => ['nav' => [], 'products' => [], 'groups' => [], 'page' => []],
        'contactSent' => false,
        'contactError' => '',
    ]);
}
