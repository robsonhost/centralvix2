{include file="$template/includes/flashmessage.tpl"}

<div class="cv-dashboard cv-fade-in">
    <div class="cv-hero cv-slide-up">
        <div class="cv-hero-bg" aria-hidden="true">
            <span class="cv-blob cv-blob-1"></span>
            <span class="cv-blob cv-blob-2"></span>
        </div>
        <div class="cv-hero-content">
            <p class="cv-hero-eyebrow cv-fade-in">Olá, <strong>{include file="$template/includes/centralvix-client-name.tpl"}</strong></p>
            <h1 class="cv-hero-title cv-slide-up">Bem-vindo à sua CentralVix.</h1>
            <p class="cv-hero-subtitle cv-slide-up">Gerencie seus serviços, pagamentos e suporte em um só lugar.</p>
        </div>
    </div>

    <div class="cv-stats-grid cv-stagger">
        <a href="{$WEB_ROOT}/clientarea.php?action=services" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-server"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.productsnumactive|default:0}">{$clientsstats.productsnumactive|default:0}</span>
                <span class="cv-stat-label">{lang key='navservices'}</span>
            </div>
        </a>

        {if $clientsstats.numdomains || $registerdomainenabled || $transferdomainenabled}
        <a href="{$WEB_ROOT}/clientarea.php?action=domains" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-globe"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.numactivedomains|default:0}">{$clientsstats.numactivedomains|default:0}</span>
                <span class="cv-stat-label">{lang key='navdomains'}</span>
            </div>
        </a>
        {elseif $condlinks.affiliates && $clientsstats.isAffiliate}
        <a href="{$WEB_ROOT}/affiliates.php" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-handshake"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.numaffiliatesignups|default:0}">{$clientsstats.numaffiliatesignups|default:0}</span>
                <span class="cv-stat-label">{lang key='affiliatessignups'}</span>
            </div>
        </a>
        {else}
        <a href="{$WEB_ROOT}/clientarea.php?action=quotes" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.numquotes|default:0}">{$clientsstats.numquotes|default:0}</span>
                <span class="cv-stat-label">{lang key='quotes'}</span>
            </div>
        </a>
        {/if}

        <a href="{$WEB_ROOT}/supporttickets.php" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-life-ring"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.numactivetickets|default:0}">{$clientsstats.numactivetickets|default:0}</span>
                <span class="cv-stat-label">{lang key='navtickets'}</span>
            </div>
        </a>

        <a href="{$WEB_ROOT}/clientarea.php?action=invoices" class="cv-stat-card cv-card-interactive cv-stat-card-accent">
            <div class="cv-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$clientsstats.numunpaidinvoices|default:0}">{$clientsstats.numunpaidinvoices|default:0}</span>
                <span class="cv-stat-label">{lang key='navinvoices'}</span>
            </div>
        </a>
    </div>

    <div class="cv-section cv-slide-up">
        <div class="cv-page-header">
            <h2 class="cv-page-header-title">Ações rápidas</h2>
        </div>
        <div class="cv-quick-actions">
            <a href="{$WEB_ROOT}/cart.php" class="cv-quick-action">
                <i class="fas fa-plus-circle"></i>
                <span>Contratar Serviço</span>
            </a>
            <a href="{$WEB_ROOT}/clientarea.php?action=services" class="cv-quick-action">
                <i class="fas fa-server"></i>
                <span>Ver Serviços</span>
            </a>
            <a href="{$WEB_ROOT}/clientarea.php?action=invoices" class="cv-quick-action">
                <i class="fas fa-credit-card"></i>
                <span>Pagar Fatura</span>
            </a>
            <a href="{$WEB_ROOT}/submitticket.php" class="cv-quick-action">
                <i class="fas fa-ticket-alt"></i>
                <span>Abrir Ticket</span>
            </a>
            <a href="{$WEB_ROOT}/clientarea.php?action=domains" class="cv-quick-action">
                <i class="fas fa-globe"></i>
                <span>Meus Domínios</span>
            </a>
            <a href="{$WEB_ROOT}/clientarea.php?action=downloads" class="cv-quick-action">
                <i class="fas fa-download"></i>
                <span>Downloads</span>
            </a>
        </div>
    </div>

    {foreach $addons_html as $addon_html}
        <div class="cv-addon-panel cv-slide-up">{$addon_html}</div>
    {/foreach}

    {if $captchaError}
        <div class="cv-alert cv-alert-danger cv-shake-once">{$captchaError}</div>
    {/if}

    {function name=outputHomePanels}
        <div menuItemName="{$item->getName()}" class="cv-card cv-panel-card cv-slide-up{if $item->getClass()} {$item->getClass()}{/if}"{if $item->getAttribute('id')} id="{$item->getAttribute('id')}"{/if}>
            <div class="cv-card-header">
                {if $item->getExtra('btn-link') && $item->getExtra('btn-text')}
                    <a href="{$item->getExtra('btn-link')}" class="cv-button cv-button-sm cv-button-ghost">
                        {if $item->getExtra('btn-icon')}<i class="{$item->getExtra('btn-icon')}"></i>{/if}
                        {$item->getExtra('btn-text')}
                    </a>
                {/if}
                <h3 class="cv-card-title">
                    {if $item->hasIcon()}<i class="{$item->getIcon()}"></i>&nbsp;{/if}
                    {$item->getLabel()}
                    {if $item->hasBadge()}&nbsp;<span class="cv-badge">{$item->getBadge()}</span>{/if}
                </h3>
            </div>
            {if $item->hasBodyHtml()}
                <div class="cv-card-body">{$item->getBodyHtml()}</div>
            {/if}
            {if $item->hasChildren()}
                <div class="cv-card-body cv-panel-list">
                    {foreach $item->getChildren() as $childItem}
                        {if $childItem->getUri()}
                            <a href="{$childItem->getUri()}" class="cv-panel-link{if $childItem->getClass()} {$childItem->getClass()}{/if}"{if $childItem->getAttribute('dataToggleTab')} data-toggle="tab"{/if}{if $childItem->getAttribute('target')} target="{$childItem->getAttribute('target')}"{/if} id="{$childItem->getId()}">
                                {if $childItem->hasIcon()}<i class="{$childItem->getIcon()}"></i>{/if}
                                {$childItem->getLabel()}
                                {if $childItem->hasBadge()}<span class="cv-badge">{$childItem->getBadge()}</span>{/if}
                            </a>
                        {else}
                            <div class="cv-panel-link{if $childItem->getClass()} {$childItem->getClass()}{/if}" id="{$childItem->getId()}">
                                {if $childItem->hasIcon()}<i class="{$childItem->getIcon()}"></i>{/if}
                                {$childItem->getLabel()}
                                {if $childItem->hasBadge()}<span class="cv-badge">{$childItem->getBadge()}</span>{/if}
                            </div>
                        {/if}
                    {/foreach}
                </div>
            {/if}
            {if $item->hasFooterHtml()}
                <div class="cv-card-footer">{$item->getFooterHtml()}</div>
            {/if}
        </div>
    {/function}

    <div class="cv-panels-grid row">
        <div class="col-12">
            {foreach $panels as $item}
                {if $item->getExtra('colspan')}
                    {outputHomePanels}
                    {assign "panels" $panels->removeChild($item->getName())}
                {/if}
            {/foreach}
        </div>
        <div class="col-md-6 col-lg-12 col-xl-6 cv-panels-col">
            {foreach $panels as $item}
                {if $item@iteration is odd}
                    {outputHomePanels}
                {/if}
            {/foreach}
        </div>
        <div class="col-md-6 col-lg-12 col-xl-6 cv-panels-col">
            {foreach $panels as $item}
                {if $item@iteration is even}
                    {outputHomePanels}
                {/if}
            {/foreach}
        </div>
    </div>
</div>
