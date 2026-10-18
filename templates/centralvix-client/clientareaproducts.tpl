{include file="$template/includes/flashmessage.tpl"}

<div class="cv-services-page cv-fade-in">
    <div class="cv-page-header">
        <div>
            <h1 class="cv-page-header-title">{lang key='navservices'}</h1>
            <p class="cv-page-header-subtitle">Gerencie hospedagem, cloud, VPS e streaming.</p>
        </div>
        <a href="{$WEB_ROOT}/cart.php" class="cv-button cv-button-primary">
            <i class="fas fa-plus"></i> Contratar Serviço
        </a>
    </div>

    {if $services|@count > 0}
    <div class="cv-service-cards-grid cv-stagger">
        {foreach $services as $service}
        <article class="cv-service-card cv-card-interactive" onclick="window.location='clientarea.php?action=productdetails&amp;id={$service.id}'">
            <div class="cv-service-card-head">
                <div class="cv-service-icon"><i class="fas fa-server"></i></div>
                <span class="cv-badge cv-status cv-status-{$service.status|strtolower}">{$service.statustext}</span>
            </div>
            <h3 class="cv-service-title">{$service.product|escape}</h3>
            {if $service.domain}
                <p class="cv-service-domain"><i class="fas fa-globe"></i> {$service.domain|escape}</p>
            {/if}
            <ul class="cv-service-meta">
                <li><span>Vencimento</span><strong>{$service.nextduedate}</strong></li>
                <li><span>Valor</span><strong>{$service.amount} <small>{$service.billingcycle}</small></strong></li>
            </ul>
            <a href="clientarea.php?action=productdetails&amp;id={$service.id}" class="cv-button cv-button-sm cv-button-primary cv-service-btn" onclick="event.stopPropagation();">
                Gerenciar Serviço
            </a>
        </article>
        {/foreach}
    </div>
    {else}
    <div class="cv-empty-state cv-slide-up">
        <div class="cv-empty-icon"><i class="fas fa-server"></i></div>
        <h3>Você ainda não possui serviços.</h3>
        <p>Contrate hospedagem, cloud ou streaming e gerencie tudo por aqui.</p>
        <a href="{$WEB_ROOT}/cart.php" class="cv-button cv-button-primary">Contratar meu primeiro serviço</a>
    </div>
    {/if}

    <div class="cv-card cv-table-card cv-slide-up">
        <div class="cv-card-header">
            <h2 class="cv-card-title">Listagem completa</h2>
        </div>
        <div class="cv-card-body">
            {include file="$template/includes/tablelist.tpl" tableName="ServicesList" filterColumn="4" noSortColumns="0"}

            <script>
                jQuery(document).ready(function () {
                    CentralVixClient.finalizeTable('tableServicesList', function (table) {
                        {if $orderby == 'product'}
                            table.order([[1, '{$sort}'], [4, 'asc']]);
                        {elseif $orderby == 'amount' || $orderby == 'billingcycle'}
                            table.order([[2, '{$sort}']]);
                        {elseif $orderby == 'nextduedate'}
                            table.order([[3, '{$sort}']]);
                        {elseif $orderby == 'domainstatus'}
                            table.order([[4, '{$sort}']]);
                        {/if}
                    });
                });
            </script>

            <div class="table-container clearfix cv-table">
                <table id="tableServicesList" class="table table-list w-hidden cv-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>{lang key='orderproduct'}</th>
                            <th>{lang key='clientareaaddonpricing'}</th>
                            <th>{lang key='clientareahostingnextduedate'}</th>
                            <th>{lang key='clientareastatus'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $services as $service}
                            <tr onclick="clickableSafeRedirect(event, 'clientarea.php?action=productdetails&amp;id={$service.id}', false)">
                                <td class="py-0 text-center{if $service.sslStatus} ssl-info{/if}" data-element-id="{$service.id}" data-type="service"{if $service.domain} data-domain="{$service.domain}"{/if}>
                                    {if $service.sslStatus}
                                        <img src="{$service.sslStatus->getImagePath()}" data-toggle="tooltip" title="{$service.sslStatus->getTooltipContent()}" class="{$service.sslStatus->getClass()}" width="25" alt="">
                                    {elseif !$service.isActive}
                                        <img src="{$BASE_PATH_IMG}/ssl/ssl-inactive-domain.png" data-toggle="tooltip" title="{lang key='sslState.sslInactiveService'}" width="25" alt="">
                                    {/if}
                                </td>
                                <td><strong>{$service.product}</strong>{if $service.domain}<br /><a href="http://{$service.domain}" target="_blank" rel="noopener">{$service.domain}</a>{else}<br />-{/if}</td>
                                <td class="text-center" data-order="{$service.amountnum}">{$service.amount} <small class="text-muted">{$service.billingcycle}</small></td>
                                <td class="text-center"><span class="w-hidden">{$service.normalisedNextDueDate}</span>{$service.nextduedate}</td>
                                <td class="text-center"><span class="cv-badge cv-status cv-status-{$service.status|strtolower}">{$service.statustext}</span></td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
                <div class="text-center" id="tableLoading">
                    <p><i class="fas fa-spinner fa-spin"></i> {lang key='loading'}</p>
                </div>
            </div>
        </div>
    </div>
</div>
