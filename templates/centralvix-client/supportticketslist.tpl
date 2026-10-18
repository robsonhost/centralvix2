{include file="$template/includes/flashmessage.tpl"}

<div class="cv-support-page cv-fade-in">
    <div class="cv-page-header">
        <div>
            <h1 class="cv-page-header-title">{lang key='navtickets'}</h1>
            <p class="cv-page-header-subtitle">Acompanhe suas solicitações de suporte.</p>
        </div>
        <a href="{$WEB_ROOT}/submitticket.php" class="cv-button cv-button-primary">
            <i class="fas fa-plus"></i> Abrir Novo Ticket
        </a>
    </div>

    {if $tickets|@count == 0}
    <div class="cv-empty-state cv-slide-up">
        <div class="cv-empty-icon"><i class="fas fa-life-ring"></i></div>
        <h3>Nenhum ticket aberto.</h3>
        <p>Precisa de ajuda? Nossa equipe está pronta para atender.</p>
        <a href="{$WEB_ROOT}/submitticket.php" class="cv-button cv-button-primary">Abrir Novo Ticket</a>
    </div>
    {/if}

    <div class="cv-card cv-table-card cv-slide-up">
        <div class="cv-card-body">
            {include file="$template/includes/tablelist.tpl" tableName="TicketsList" filterColumn="2"}

            <script>
                jQuery(document).ready(function () {
                    CentralVixClient.finalizeTable('tableTicketsList', function (table) {
                        {if $orderby == 'did' || $orderby == 'dept'}
                            table.order([[0, '{$sort}']]);
                        {elseif $orderby == 'subject' || $orderby == 'title'}
                            table.order([[1, '{$sort}']]);
                        {elseif $orderby == 'status'}
                            table.order([[2, '{$sort}']]);
                        {elseif $orderby == 'lastreply'}
                            table.order([[3, '{$sort}']]);
                        {/if}
                    });
                });
            </script>

            <div class="table-container clearfix cv-table">
                <table id="tableTicketsList" class="table table-list w-hidden">
                    <thead>
                        <tr>
                            <th>{lang key='supportticketsdepartment'}</th>
                            <th>{lang key='supportticketssubject'}</th>
                            <th>{lang key='supportticketsstatus'}</th>
                            <th>{lang key='supportticketsticketlastupdated'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $tickets as $ticket}
                            <tr onclick="window.location='viewticket.php?tid={$ticket.tid}&amp;c={$ticket.c}'">
                                <td>{$ticket.department}</td>
                                <td>
                                    <a href="viewticket.php?tid={$ticket.tid}&amp;c={$ticket.c}" class="cv-ticket-link" onclick="event.stopPropagation();">
                                        <span class="cv-ticket-num">#{$ticket.tid}</span>
                                        <span class="cv-ticket-subject{if $ticket.unread} cv-unread{/if}">{$ticket.subject|escape}</span>
                                    </a>
                                </td>
                                <td>
                                    <span class="cv-badge cv-status {if is_null($ticket.statusColor)}cv-status-{$ticket.statusClass}{else}cv-status-custom{/if}"{if !is_null($ticket.statusColor)} style="background-color:{$ticket.statusColor}"{/if}>
                                        {$ticket.status|strip_tags}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="w-hidden">{$ticket.normalisedLastReply}</span>
                                    {$ticket.lastreply}
                                </td>
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
