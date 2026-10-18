<div class="cv-landing">
    <header class="cv-landing-header">
        <div class="cv-landing-wrap">
            <a class="cv-landing-brand" href="{$WEB_ROOT}/index.php" aria-label="CENTRALVIX">
                <span class="cv-landing-brand-name">{$cvix.brand_prefix|default:'CENTRAL'}<span>{$cvix.brand_accent|default:'VIX'}</span></span>
                <span class="cv-landing-brand-tag">{$cvix.tagline|default:'AUTOMAÇÃO & SISTEMAS'}</span>
            </a>

            <nav class="cv-landing-nav" aria-label="Principal">
                <a href="#recursos">Recursos</a>
                <a href="{$WEB_ROOT}/cart.php">Planos</a>
                <a href="#beneficios">Benefícios</a>
                <a href="#sobre">Sobre</a>
                <a href="{$WEB_ROOT}/contact.php">Contato</a>
            </nav>

            <div class="cv-landing-header-actions">
                <a class="cv-landing-btn-client" href="#cv-landing-login">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <span>Área do Cliente</span>
                </a>
                <button type="button" class="cv-landing-burger" id="cv-landing-burger" aria-label="Abrir menu" aria-expanded="false" aria-controls="cv-landing-mobile-panel">
                    <i class="fas fa-bars" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    <div class="cv-landing-mobile-panel" id="cv-landing-mobile-panel">
        <a href="#recursos">Recursos</a>
        <a href="{$WEB_ROOT}/cart.php">Planos</a>
        <a href="#beneficios">Benefícios</a>
        <a href="#sobre">Sobre</a>
        <a href="{$WEB_ROOT}/contact.php">Contato</a>
        <a href="#cv-landing-login">Área do Cliente</a>
    </div>

    <section class="cv-landing-hero">
        <div class="cv-landing-wrap cv-landing-hero-inner">
            <div>
                <div class="cv-landing-badge" id="recursos">
                    <i class="fas fa-shield-alt" aria-hidden="true"></i>
                    {$cvix.badge|default:'SISTEMA CENTRALVIX'}
                </div>
                <h1 class="cv-landing-title">
                    {$cvix.hero_title_before|default:'Gestão completa para'}<br>
                    {$cvix.hero_title_after|default:'seu negócio'}
                    <em>{$cvix.hero_title_highlight|default:'crescer'}</em>
                </h1>
                <p class="cv-landing-lead">{$cvix.hero_text|default:'A CENTRALVIX é a plataforma ideal para provedores, revendedores e empresas que buscam automação, controle e eficiência em um só lugar.'}</p>

                <div class="cv-landing-features">
                    {if $cvix.differentials}
                        {foreach $cvix.differentials as $item}
                            <div class="cv-landing-feature">
                                <i class="{$item.icon}" aria-hidden="true"></i>
                                <div>
                                    <strong>{$item.title}</strong>
                                    <span>{$item.text}</span>
                                </div>
                            </div>
                        {/foreach}
                    {else}
                        <div class="cv-landing-feature"><i class="fas fa-cogs" aria-hidden="true"></i><div><strong>Automação</strong><span>Processos automáticos e inteligentes</span></div></div>
                        <div class="cv-landing-feature"><i class="fas fa-chart-line" aria-hidden="true"></i><div><strong>Relatórios</strong><span>Dashboards completos e em tempo real</span></div></div>
                        <div class="cv-landing-feature"><i class="fas fa-shield-alt" aria-hidden="true"></i><div><strong>Segurança</strong><span>Dados protegidos com alta tecnologia</span></div></div>
                        <div class="cv-landing-feature"><i class="fas fa-plug" aria-hidden="true"></i><div><strong>Integrações</strong><span>Compatível com as principais ferramentas</span></div></div>
                    {/if}
                </div>

                <div class="cv-landing-stats">
                    {if $cvix.stats}
                        {foreach $cvix.stats as $stat}
                            <div class="cv-landing-stat">
                                <i class="{$stat.icon}" aria-hidden="true"></i>
                                <div>
                                    <b>{$stat.value}</b>
                                    <small>{$stat.label}</small>
                                </div>
                            </div>
                        {/foreach}
                    {else}
                        <div class="cv-landing-stat"><i class="fas fa-users" aria-hidden="true"></i><div><b>+5.000</b><small>Clientes ativos</small></div></div>
                        <div class="cv-landing-stat"><i class="fas fa-check-circle" aria-hidden="true"></i><div><b>+98%</b><small>Satisfação</small></div></div>
                        <div class="cv-landing-stat"><i class="fas fa-clock" aria-hidden="true"></i><div><b>99,9%</b><small>Disponibilidade</small></div></div>
                    {/if}
                </div>
            </div>

            <aside class="cv-landing-card" id="cv-landing-login" aria-labelledby="cv-landing-login-title">
                {if $remote_auth_prelinked}
                    <div class="alert alert-warning">{lang key='remoteAuthn.oneTimeAuthRequired'}</div>
                {/if}

                <h2 id="cv-landing-login-title">Acesse sua conta</h2>
                <p class="cv-landing-card-sub">Entre com suas credenciais para acessar o sistema</p>

                {include file="$template/includes/flashmessage.tpl"}

                <form method="post" action="{routePath('login-validate')}" class="login-form" id="cv-landing-login-form" role="form">
                    <input type="hidden" name="token" value="{$token}" />

                    <div class="cv-landing-field">
                        <label for="inputEmail">Usuário ou E-mail</label>
                        <div class="cv-landing-input">
                            <i class="fas fa-user cv-landing-input-icon" aria-hidden="true"></i>
                            <input type="text" class="form-control" name="username" id="inputEmail" placeholder="Usuário ou e-mail" autocomplete="username" autofocus required>
                        </div>
                    </div>

                    <div class="cv-landing-field">
                        <label for="inputPassword">Senha</label>
                        <div class="cv-landing-input">
                            <i class="fas fa-lock cv-landing-input-icon" aria-hidden="true"></i>
                            <input type="password" class="form-control pw-input" name="password" id="inputPassword" placeholder="Senha" autocomplete="current-password" required>
                            <button class="cv-landing-toggle-pw btn-reveal-pw" id="cv-landing-toggle-password" type="button" aria-label="Mostrar senha">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    {if $captcha->isEnabled()}
                        <div class="cv-landing-field">{include file="$template/includes/captcha.tpl"}</div>
                    {/if}

                    <div class="cv-landing-row">
                        <label class="cv-landing-check">
                            <input type="checkbox" name="rememberme" value="1">
                            Lembrar-me
                        </label>
                        <a class="cv-landing-forgot" href="{routePath('password-reset-begin')}">Esqueci minha senha</a>
                    </div>

                    <button id="cv-landing-submit" type="submit" class="cv-landing-submit{$captcha->getButtonClass($captchaForm)}">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        Entrar no Sistema
                    </button>
                </form>

                <div class="cv-landing-divider">ou</div>

                <a class="cv-landing-secondary" href="{$WEB_ROOT}/clientarea.php?action=invoices">
                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                    Consulta de 2ª Via
                </a>

                <p class="cv-landing-register">
                    {lang key='userLogin.notRegistered'}
                    <a href="{routePath('register')}">{lang key='userLogin.createAccount'}</a>
                </p>

                {include file="$template/includes/linkedaccounts.tpl" linkContext="login" customFeedback=true}
            </aside>
        </div>
    </section>

    <section class="cv-landing-why" id="beneficios">
        <div class="cv-landing-wrap">
            <p class="cv-landing-why-kicker">POR QUE ESCOLHER A CENTRALVIX?</p>
            <h2>Tudo que você precisa em uma única plataforma</h2>
            <div class="cv-landing-why-grid">
                {if $cvix.benefits}
                    {foreach $cvix.benefits as $item}
                        <article class="cv-landing-why-card">
                            <div class="cv-landing-why-icon"><i class="{$item.icon}" aria-hidden="true"></i></div>
                            <h3>{$item.title}</h3>
                            <p>{$item.text}</p>
                        </article>
                    {/foreach}
                {else}
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></div><h3>Confiável</h3><p>Sistema estável e seguro para o seu negócio</p></article>
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-rocket" aria-hidden="true"></i></div><h3>Ágil</h3><p>Interface moderna e fácil de usar</p></article>
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-cloud" aria-hidden="true"></i></div><h3>Na Nuvem</h3><p>Acesse de qualquer lugar com segurança</p></article>
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-headset" aria-hidden="true"></i></div><h3>Suporte Especializado</h3><p>Equipe pronta para te ajudar sempre</p></article>
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-sync-alt" aria-hidden="true"></i></div><h3>Atualizações Constantes</h3><p>Novas funcionalidades regularmente</p></article>
                    <article class="cv-landing-why-card"><div class="cv-landing-why-icon"><i class="fas fa-award" aria-hidden="true"></i></div><h3>Qualidade Garantida</h3><p>Anos de experiência em soluções digitais</p></article>
                {/if}
            </div>
        </div>
    </section>

    <footer class="cv-landing-footer" id="sobre">
        <div class="cv-landing-wrap cv-landing-footer-grid">
            <div>
                <a class="cv-landing-brand" href="{$WEB_ROOT}/index.php">
                    <span class="cv-landing-brand-name">{$cvix.brand_prefix|default:'CENTRAL'}<span>{$cvix.brand_accent|default:'VIX'}</span></span>
                    <span class="cv-landing-brand-tag">{$cvix.tagline|default:'AUTOMAÇÃO & SISTEMAS'}</span>
                </a>
                <p style="margin-top:1rem;">{$cvix.footer_text}</p>
                {if isset($cvix.social) && ($cvix.social.facebook || $cvix.social.instagram || $cvix.social.youtube)}
                    <div class="cv-landing-social">
                        {if $cvix.social.facebook}<a href="{$cvix.social.facebook|escape:'html'}" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>{/if}
                        {if $cvix.social.instagram}<a href="{$cvix.social.instagram|escape:'html'}" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>{/if}
                        {if $cvix.social.youtube}<a href="{$cvix.social.youtube|escape:'html'}" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>{/if}
                    </div>
                {/if}
            </div>
            <div>
                <h4>NAVEGAÇÃO</h4>
                <ul>
                    <li><a href="#recursos">Recursos</a></li>
                    <li><a href="{$WEB_ROOT}/cart.php">Planos</a></li>
                    <li><a href="#beneficios">Benefícios</a></li>
                    <li><a href="#sobre">Sobre</a></li>
                    <li><a href="{$WEB_ROOT}/contact.php">Contato</a></li>
                </ul>
            </div>
            <div>
                <h4>SUPORTE</h4>
                <ul>
                    <li><a href="{$WEB_ROOT}/knowledgebase.php">Base de Conhecimento</a></li>
                    <li><a href="{$WEB_ROOT}/submitticket.php">Abrir Ticket</a></li>
                    <li><a href="{$WEB_ROOT}/serverstatus.php">Status do Sistema</a></li>
                    <li><a href="{$WEB_ROOT}/knowledgebase.php">Tutoriais</a></li>
                    <li><a href="{$WEB_ROOT}/downloads.php">Downloads</a></li>
                </ul>
            </div>
            <div>
                <h4>CONTATO</h4>
                <ul>
                    {if $cvix.phone}<li><a href="tel:{$cvix.phone|replace:' ':''}">{$cvix.phone}</a></li>{/if}
                    {if $cvix.email}<li><a href="mailto:{$cvix.email}">{$cvix.email}</a></li>{/if}
                    {if $cvix.hours}<li>{$cvix.hours}</li>{/if}
                    <li><a href="{$WEB_ROOT}/contact.php">{lang key='contactus'}</a></li>
                </ul>
            </div>
            <div>
                <h4>SEGURANÇA</h4>
                <p>Seus dados estão protegidos com criptografia e as melhores práticas de segurança.</p>
                <div class="cv-landing-ssl"><i class="fas fa-lock" aria-hidden="true"></i> SSL Seguro</div>
            </div>
        </div>
        <div class="cv-landing-wrap cv-landing-copy">
            © {$date_year} CENTRALVIX - Automação & Sistemas. Todos os direitos reservados.
        </div>
    </footer>
</div>
