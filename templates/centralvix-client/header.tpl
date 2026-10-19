<!-- cvix-header-executed tpl={$template} file={$templatefile} cart={$inShoppingCart|default:'?'} -->
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="{$charset}" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">
    <title>{if isset($kbarticle) && $kbarticle.title}{$kbarticle.title} - {/if}{$pagetitle} - {$companyname}</title>
    {$headoutput}
    {include file="$template/includes/head.tpl"}
    <style id="cv-critical-inline">
        /* Fallback se CSS externo falhar (FTP/cache) */
        .w-hidden,.modal:not(.show),#fullpage-overlay.w-hidden,#accountNotificationsContent,.popover{display:none!important}
        .cv-client-body{font-family:'Open Sans',Segoe UI,sans-serif;background:#F5F8FC;color:#0B1F3A;margin:0}
        .cv-client-app{display:flex;min-height:100vh}
        .cv-sidebar{position:fixed;top:0;left:0;bottom:0;width:270px;background:#071426;color:#fff;z-index:1050;overflow-y:auto}
        .cv-main{flex:1;margin-left:270px;min-width:0;display:flex;flex-direction:column}
        .cv-topbar{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;background:#fff;border-bottom:1px solid #D8E3F0;position:sticky;top:0;z-index:1020}
        .cv-page{flex:1;padding:1.25rem 1.5rem}
        .cv-nav-list{list-style:none;padding:0;margin:0}
        .cv-nav-link{display:flex;align-items:center;gap:.75rem;padding:.7rem .85rem;color:rgba(255,255,255,.85);text-decoration:none}
        .cv-nav-item.active .cv-nav-link{background:rgba(15,76,255,.3);color:#fff}
        .cv-hero{background:linear-gradient(135deg,#071426,#0F4CFF);color:#fff;padding:2rem;border-radius:14px;margin-bottom:1.5rem}
        .cv-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
        .cv-stat-card,.cv-card{background:#fff;border:1px solid #D8E3F0;border-radius:14px;padding:1rem;text-decoration:none;color:inherit}
        .cv-button-primary{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1rem;background:linear-gradient(135deg,#0F4CFF,#00C8FF);color:#fff!important;border-radius:10px;text-decoration:none;font-weight:600;border:0}
        @media(max-width:991px){.cv-main{margin-left:0}.cv-sidebar{transform:translateX(-100%)}.cv-sidebar.cv-sidebar-open{transform:translateX(0)}}
    </style>
    <!-- cvix-header-v1.3.1 -->
</head>
<body class="primary-bg-color cv-client-body{if $loggedin && !$inShoppingCart} cv-logged-in{/if}{if $inShoppingCart} cv-checkout{/if}{if $templatefile == 'login'} cv-page-login{/if}{if $templatefile == 'clientregister'} cv-page-register{/if}" data-cv-theme="system" data-phone-cc-input="{$phoneNumberInputStyle}" data-page="{$templatefile|escape:'html'}">
    <!-- cvix-wrap-marker:v1.3.1 -->
    {if $captcha}{$captcha->getMarkup()}{/if}
    {$headeroutput}

    {if $inShoppingCart}
        {* Checkout / carrinho: layout Twenty-One preservado para compatibilidade com order forms *}
        <header id="header" class="header cv-checkout-header">
            <div class="navbar navbar-light">
                <div class="container">
                    <a class="navbar-brand mr-3" href="{$WEB_ROOT}/index.php">
                        {if $assetLogoPath}
                            <img src="{$assetLogoPath}" alt="{$companyname}" class="logo-img">
                        {else}
                            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-dark.svg" alt="CentralVix" class="cv-logo cv-logo-full">
                        {/if}
                    </a>
                    <ul class="navbar-nav toolbar ml-auto">
                        <li class="nav-item">
                            <a class="btn nav-link cart-btn" href="{$WEB_ROOT}/cart.php?a=view">
                                <i class="far fa-shopping-cart fa-fw"></i>
                                <span id="cartItemCount" class="badge badge-info">{$cartitemcount}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        {include file="$template/includes/network-issues-notifications.tpl"}
        <section id="main-body" class="cv-checkout-body">
            <div class="{if !$skipMainBodyContainer}container{/if}">
                <div class="primary-content">

    {elseif $templatefile == 'login' || $templatefile == 'clientregister' || $templatefile == 'password-reset-container'}
        {* Autenticacao: shell visual definido nos tpl de login/cadastro *}
        <div class="cv-auth-shell">

    {elseif $loggedin}
        {* Area logada: sidebar + topbar SaaS *}
        <div class="cv-client-app" id="cv-client-app">
            {include file="$template/includes/centralvix-sidebar.tpl"}
            <div class="cv-sidebar-overlay" id="cv-sidebar-overlay" hidden></div>
            <div class="cv-main">
                {include file="$template/includes/centralvix-topbar.tpl"}
                <main class="cv-page" id="main-body">
                    {include file="$template/includes/network-issues-notifications.tpl"}
                    {include file="$template/includes/validateuser.tpl"}
                    {include file="$template/includes/verifyemail.tpl"}
                    <div class="cv-page-inner">
                        {if $templatefile != 'clientareahome' && $templatefile != 'clientarea'}
                            {include file="$template/includes/breadcrumb.tpl"}
                        {/if}
                        <div class="cv-page-content primary-content">

    {else}
        {* Publico (KB, contato, etc.) *}
        <header id="header" class="header cv-public-header">
            <div class="navbar navbar-light">
                <div class="container">
                    <a class="navbar-brand" href="{$WEB_ROOT}/index.php">
                        {if $assetLogoPath}
                            <img src="{$assetLogoPath}" alt="{$companyname}" class="logo-img">
                        {else}
                            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-dark.svg" alt="CentralVix" class="cv-logo cv-logo-full">
                        {/if}
                    </a>
                    <ul class="navbar-nav ml-auto flex-row">
                        <li class="nav-item mr-2">
                            <a class="btn cv-button cv-button-ghost" href="{$WEB_ROOT}/clientarea.php">{lang key='loginbutton'}</a>
                        </li>
                        {if !$loggedin}
                        <li class="nav-item">
                            <a class="btn cv-button cv-button-primary" href="{$WEB_ROOT}/register.php">{lang key='userLogin.createAccount'}</a>
                        </li>
                        {/if}
                    </ul>
                </div>
            </div>
        </header>
        {include file="$template/includes/network-issues-notifications.tpl"}
        <section id="main-body">
            <div class="{if !$skipMainBodyContainer}container{/if}">
                <div class="primary-content">

    {/if}
