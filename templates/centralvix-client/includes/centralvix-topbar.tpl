<header class="cv-topbar" id="cv-topbar">
    <div class="cv-topbar-left">
        <button type="button" class="cv-icon-btn cv-mobile-menu d-lg-none" id="cv-mobile-menu" aria-label="Abrir menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="cv-topbar-greeting d-none d-md-block">
            <span class="cv-greeting-label">Olá,</span>
            <strong class="cv-greeting-name">{include file="$template/includes/centralvix-client-name.tpl"}</strong>
        </div>
    </div>

    <div class="cv-topbar-right">
        <div class="cv-theme-switch" role="group" aria-label="Tema">
            <button type="button" class="cv-theme-btn" data-cv-theme-mode="light" title="Claro"><i class="fas fa-sun"></i></button>
            <button type="button" class="cv-theme-btn" data-cv-theme-mode="dark" title="Escuro"><i class="fas fa-moon"></i></button>
            <button type="button" class="cv-theme-btn active" data-cv-theme-mode="system" title="Sistema"><i class="fas fa-desktop"></i></button>
        </div>

        <div class="cv-topbar-notifications">
            <button type="button" class="cv-icon-btn" data-toggle="popover" id="accountNotifications" data-placement="bottom" aria-label="{lang key='notifications'}">
                <i class="far fa-bell"></i>
                {if count($clientAlerts) > 0}
                    <span class="cv-badge cv-badge-danger cv-badge-dot">{count($clientAlerts)}</span>
                {/if}
            </button>
            <div id="accountNotificationsContent" class="w-hidden">
                <ul class="client-alerts cv-alert-list">
                    {foreach $clientAlerts as $alert}
                        <li>
                            <a href="{$alert->getLink()}">
                                <i class="fas fa-fw fa-{if $alert->getSeverity() == 'danger'}exclamation-circle{elseif $alert->getSeverity() == 'warning'}exclamation-triangle{elseif $alert->getSeverity() == 'info'}info-circle{else}check-circle{/if}"></i>
                                <span>{$alert->getMessage()}</span>
                            </a>
                        </li>
                    {foreachelse}
                        <li class="none">{lang key='notificationsnone'}</li>
                    {/foreach}
                </ul>
            </div>
        </div>

        <a href="{$WEB_ROOT}/cart.php?a=view" class="cv-icon-btn cv-cart-btn" title="{lang key='carttitle'}">
            <i class="far fa-shopping-cart"></i>
            <span class="cv-badge cv-badge-info">{$cartitemcount}</span>
        </a>

        <div class="cv-account-menu dropdown" id="cv-account-dropdown">
            <button class="cv-account-btn dropdown-toggle" type="button" id="cv-account-menu-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Menu da conta">
                <span class="cv-avatar"><i class="fas fa-user"></i></span>
            </button>
            <div class="dropdown-menu dropdown-menu-right cv-dropdown" aria-labelledby="cv-account-menu-btn">
                <a class="dropdown-item" href="{$WEB_ROOT}/clientarea.php?action=details">
                    <i class="fas fa-user-circle"></i> {lang key='clientareanavdetails'}
                </a>
                <a class="dropdown-item" href="{routePath('user-accounts')}">
                    <i class="fas fa-users"></i> Trocar conta
                </a>
                {if $adminMasqueradingAsClient || $adminLoggedIn}
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{$WEB_ROOT}/logout.php?returntoadmin=1">
                        <i class="fas fa-user-shield"></i> {lang key="admin.returnToAdmin"}
                    </a>
                {/if}
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{$WEB_ROOT}/logout.php">
                    <i class="fas fa-sign-out-alt"></i> {lang key='logouttitle'}
                </a>
            </div>
        </div>
    </div>
</header>
