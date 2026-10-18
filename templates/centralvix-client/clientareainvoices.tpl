{include file="$template/includes/flashmessage.tpl"}

{assign var="cvInvOpen" value=0}
{assign var="cvInvOverdue" value=0}
{assign var="cvInvPaid" value=0}
{foreach $invoices as $inv}
    {if $inv.statusClass == 'paid' || $inv.rawstatus == 'Paid'}
        {assign var="cvInvPaid" value=$cvInvPaid+1}
    {elseif $inv.statusClass == 'overdue' || $inv.rawstatus == 'Overdue'}
        {assign var="cvInvOverdue" value=$cvInvOverdue+1}
        {assign var="cvInvOpen" value=$cvInvOpen+1}
    {elseif $inv.statusClass == 'unpaid' || $inv.rawstatus == 'Unpaid'}
        {assign var="cvInvOpen" value=$cvInvOpen+1}
    {/if}
{/foreach}

<div class="cv-finance-page cv-fade-in">
    <div class="cv-page-header">
        <div>
            <h1 class="cv-page-header-title">{lang key='navinvoices'}</h1>
            <p class="cv-page-header-subtitle">Resumo financeiro e pagamentos.</p>
        </div>
    </div>

    <div class="cv-stats-grid cv-stats-grid-3 cv-stagger">
        <div class="cv-stat-card">
            <div class="cv-stat-icon"><i class="fas fa-clock"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$cvInvOpen}">{$cvInvOpen}</span>
                <span class="cv-stat-label">Em aberto</span>
            </div>
        </div>
        <div class="cv-stat-card cv-stat-card-danger">
            <div class="cv-stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$cvInvOverdue}">{$cvInvOverdue}</span>
                <span class="cv-stat-label">Vencidas</span>
            </div>
        </div>
        <div class="cv-stat-card cv-stat-card-success">
            <div class="cv-stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="{$cvInvPaid}">{$cvInvPaid}</span>
                <span class="cv-stat-label">Pagas</span>
            </div>
        </div>
    </div>

    {if $invoices|@count == 0}
    <div class="cv-empty-state cv-slide-up">
        <div class="cv-empty-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <h3>Nenhuma fatura encontrada.</h3>
        <p>Suas faturas aparecerão aqui quando forem geradas.</p>
    </div>
    {/if}

    <div class="cv-card cv-table-card cv-slide-up">
        <div class="cv-card-body">
            {include file="$template/includes/tablelist.tpl" tableName="InvoicesList" filterColumn="4"}

            <script>
                jQuery(document).ready(function() {
                    CentralVixClient.finalizeTable('tableInvoicesList', function (table) {
                        {if $orderby == 'default'}
                            table.order([[4, 'desc'], [2, 'asc']]);
                        {elseif $orderby == 'invoicenum'}
                            table.order([[0, '{$sort}']]);
                        {elseif $orderby == 'date'}
                            table.order([[1, '{$sort}']]);
                        {elseif $orderby == 'duedate'}
                            table.order([[2, '{$sort}']]);
                        {elseif $orderby == 'total'}
                            table.order([[3, '{$sort}']]);
                        {elseif $orderby == 'status'}
                            table.order([[4, '{$sort}']]);
                        {/if}
                    });
                });
            </script>

            <div class="table-container clearfix cv-table">
                <table id="tableInvoicesList" class="table table-list w-hidden">
                    <thead>
                        <tr>
                            <th>{lang key='invoicestitle'}</th>
                            <th>{lang key='invoicesdatecreated'}</th>
                            <th>{lang key='invoicesdatedue'}</th>
                            <th>{lang key='invoicestotal'}</th>
                            <th>{lang key='invoicesstatus'}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $invoices as $invoice}
                            <tr>
                                <td><a href="viewinvoice.php?id={$invoice.id}">{$invoice.invoicenum}</a></td>
                                <td><span class="w-hidden">{$invoice.normalisedDateCreated}</span>{$invoice.datecreated}</td>
                                <td><span class="w-hidden">{$invoice.normalisedDateDue}</span>{$invoice.datedue}</td>
                                <td data-order="{$invoice.totalnum}">{$invoice.total}</td>
                                <td><span class="cv-badge cv-status cv-status-{$invoice.statusClass}">{$invoice.status}</span></td>
                                <td class="text-right">
                                    {if $invoice.statusClass == 'unpaid' || $invoice.statusClass == 'overdue'}
                                        <a href="viewinvoice.php?id={$invoice.id}" class="cv-button cv-button-sm cv-button-primary">PAGAR AGORA</a>
                                    {/if}
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
