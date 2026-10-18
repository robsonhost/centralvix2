<aside class="cv-sidebar" id="cv-sidebar" aria-label="Menu principal">
    <div class="cv-sidebar-head">
        <a href="{$WEB_ROOT}/clientarea.php" class="cv-brand cv-brand-full" title="CentralVix">
            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-light.svg" alt="CentralVix" class="cv-logo cv-logo-full">
        </a>
        <a href="{$WEB_ROOT}/clientarea.php" class="cv-brand cv-brand-mini" title="CentralVix">
            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-icon.svg" alt="CentralVix" class="cv-logo cv-logo-icon">
        </a>
        <button type="button" class="cv-sidebar-toggle d-none d-lg-inline-flex" id="cv-sidebar-toggle" aria-label="Recolher menu" aria-expanded="true">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>

    <nav class="cv-sidebar-nav">
        <ul class="cv-nav-list">
            <li class="cv-nav-item{if $templatefile == 'clientarea' || $templatefile == 'clientareahome'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-home"></i></span>
                    <span class="cv-nav-label">Início</span>
                </a>
            </li>
            <li class="cv-nav-item{if $templatefile == 'clientareaproducts' || $templatefile == 'clientareaproductdetails'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php?action=services" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-server"></i></span>
                    <span class="cv-nav-label">{lang key='navservices'}</span>
                </a>
            </li>
            <li class="cv-nav-item{if $templatefile == 'clientareadomains'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php?action=domains" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-globe"></i></span>
                    <span class="cv-nav-label">{lang key='navdomains'}</span>
                </a>
            </li>
            <li class="cv-nav-item{if $templatefile == 'clientareainvoices' || $templatefile == 'viewinvoice'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php?action=invoices" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>
                    <span class="cv-nav-label">{lang key='navinvoices'}</span>
                </a>
            </li>
            <li class="cv-nav-item{if $templatefile == 'supportticketslist' || $templatefile == 'viewticket' || $templatefile == 'submitticket'} active{/if}">
                <a href="{$WEB_ROOT}/supporttickets.php" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-life-ring"></i></span>
                    <span class="cv-nav-label">{lang key='navtickets'}</span>
                </a>
            </li>
        </ul>

        <div class="cv-nav-divider"></div>
        <p class="cv-nav-section">Mais</p>
        <ul class="cv-nav-list">
            <li class="cv-nav-item{if $templatefile == 'clientareadetails'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php?action=details" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-user"></i></span>
                    <span class="cv-nav-label">{lang key='clientareanavdetails'}</span>
                </a>
            </li>
            <li class="cv-nav-item{if $templatefile == 'downloads'} active{/if}">
                <a href="{$WEB_ROOT}/clientarea.php?action=downloads" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-download"></i></span>
                    <span class="cv-nav-label">{lang key='downloads'}</span>
                </a>
            </li>
            {if $condlinks.affiliates}
            <li class="cv-nav-item{if $templatefile == 'affiliates'} active{/if}">
                <a href="{$WEB_ROOT}/affiliates.php" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-handshake"></i></span>
                    <span class="cv-nav-label">{lang key='affiliates'}</span>
                </a>
            </li>
            {/if}
            <li class="cv-nav-item{if $templatefile == 'announcements'} active{/if}">
                <a href="{$WEB_ROOT}/announcements.php" class="cv-nav-link">
                    <span class="cv-nav-icon"><i class="fas fa-bullhorn"></i></span>
                    <span class="cv-nav-label">{lang key='announcements'}</span>
                </a>
            </li>
            <li class="cv-nav-item">
                <a href="{$WEB_ROOT}/logout.php" class="cv-nav-link cv-nav-logout">
                    <span class="cv-nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="cv-nav-label">{lang key='logouttitle'}</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
