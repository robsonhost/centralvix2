{include file="$template/includes/centralvix-page-open.tpl"}
{include file="$template/includes/tablelist.tpl" tableName="EmailsList" noSortColumns="-1"}

<script>
    jQuery(document).ready(function () {
        CentralVixClient.finalizeTable('tableEmailsList', function (table) {
            {if $orderby == 'date'}
                table.order([[0, '{$sort}']]);
            {elseif $orderby == 'subject'}
                table.order([[1, '{$sort}']]);
            {/if}
        });
    });
</script>

<div class="table-container clearfix">
    <table id="tableEmailsList" class="table table-list w-hidden">
        <thead>
            <tr>
                <th>{lang key='clientareaemailsdate'}</th>
                <th>{lang key='clientareaemailssubject'}</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            {foreach $emails as $email}
                <tr onclick="popupWindow('viewemail.php?id={$email.id}', 'emailWin', '800', '600')">
                    <td class="text-center"><span class="w-hidden">{$email.normalisedDate}</span>{$email.date}</td>
                    <td>{$email.subject}{if $email.attachmentCount > 0} <i class="fal fa-paperclip"></i>{/if}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-info btn-sm text-nowrap" onclick="popupWindow('viewemail.php?id={$email.id}', 'emailWin', '800', '600', 'scrollbars=1,')">
                            {lang key='emailviewmessage'}
                        </button>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
    <div class="text-center" id="tableLoading">
        <p><i class="fas fa-spinner fa-spin"></i> {lang key='loading'}</p>
    </div>
</div>

{include file="$template/includes/centralvix-page-close.tpl"}
