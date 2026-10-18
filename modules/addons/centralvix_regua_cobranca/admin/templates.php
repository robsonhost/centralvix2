<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$editId = (int)($_GET['edit'] ?? 0);
$edit = $editId ? CentralVixReguaTemplateService::get($editId) : null;
$previewInvoiceId = (int)($_GET['preview_invoice'] ?? 0);
?>
<div class="cvix-rc-panel">
    <h3><?php echo $edit ? 'Editar template' : 'Novo template'; ?></h3>
    <form method="post" action="<?php echo CentralVixReguaSecurity::e($modulelink . '&tab=templates'); ?>" class="cvix-rc-form">
        <input type="hidden" name="token" value="<?php echo CentralVixReguaSecurity::e($csrfToken); ?>">
        <input type="hidden" name="cvix_save_template" value="1">
        <input type="hidden" name="id" value="<?php echo (int)($edit->id ?? 0); ?>">
        <label>Nome<input name="name" required value="<?php echo CentralVixReguaSecurity::e($edit->name ?? ''); ?>"></label>
        <label>Assunto e-mail<input name="subject" value="<?php echo CentralVixReguaSecurity::e($edit->subject ?? ''); ?>"></label>
        <label>Corpo e-mail (HTML)<textarea name="body_email" rows="6"><?php echo CentralVixReguaSecurity::e($edit->body_email ?? ''); ?></textarea></label>
        <label>Corpo WhatsApp<textarea name="body_whatsapp" rows="5"><?php echo CentralVixReguaSecurity::e($edit->body_whatsapp ?? ''); ?></textarea></label>
        <label><input type="checkbox" name="is_payment_confirmation" value="1" <?php echo !empty($edit->is_payment_confirmation) ? 'checked' : ''; ?>> Template de pagamento confirmado</label>
        <div class="cvix-rc-placeholders">
            <strong>Placeholders:</strong>
            <?php foreach ($placeholders as $ph => $desc): ?>
            <code title="<?php echo CentralVixReguaSecurity::e($desc); ?>"><?php echo CentralVixReguaSecurity::e($ph); ?></code>
            <?php endforeach; ?>
        </div>
        <div class="cvix-rc-preview-row">
            <label>Fatura para preview (ID)<input type="number" id="cvixPreviewInvoiceId" value="<?php echo $previewInvoiceId; ?>" min="0"></label>
            <button type="button" class="cvix-rc-btn" id="cvixPreviewBtn">Visualizar mensagem</button>
        </div>
        <div id="cvixPreviewBox" class="cvix-rc-preview-box hidden">
            <h4>Pré-visualização</h4>
            <p><strong>Assunto:</strong> <span id="cvixPreviewSubject"></span></p>
            <div><strong>E-mail:</strong><div id="cvixPreviewEmail" class="cvix-rc-preview-html"></div></div>
            <p><strong>WhatsApp:</strong></p>
            <pre id="cvixPreviewWhatsapp"></pre>
        </div>
        <button type="submit" class="cvix-rc-btn primary">Salvar template</button>
    </form>
</div>

<div class="cvix-rc-panel">
    <h3>Templates</h3>
    <table class="cvix-rc-table">
        <thead><tr><th>Nome</th><th>Tipo</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($templates as $t): ?>
        <tr>
            <td><?php echo CentralVixReguaSecurity::e($t->name); ?></td>
            <td><?php echo $t->is_payment_confirmation ? 'Pagamento confirmado' : 'Cobrança'; ?></td>
            <td><a href="<?php echo CentralVixReguaSecurity::e($modulelink . '&tab=templates&edit=' . $t->id); ?>" class="cvix-rc-btn sm">Editar</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
window.CVIX_TEMPLATE_FORM = {
    subject: <?php echo json_encode($edit->subject ?? ''); ?>,
    body_email: <?php echo json_encode($edit->body_email ?? ''); ?>,
    body_whatsapp: <?php echo json_encode($edit->body_whatsapp ?? ''); ?>
};
</script>
