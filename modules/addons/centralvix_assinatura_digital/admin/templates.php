<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$templates = CentralVixSignDocumentService::allTemplates();
?>
<h2>Templates</h2>
<?php if ($success): ?><div class="cvix-sig-alert ok"><?php echo CentralVixSignSecurity::e($success); ?></div><?php endif; ?>
<form method="post" class="cvix-sig-form cvix-sig-panel">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSignSecurity::e($csrf); ?>">
<input type="hidden" name="save_template" value="1">
<label>Nome<input type="text" name="name" required></label>
<label>Título padrão<input type="text" name="title"></label>
<label>Descrição<textarea name="description" rows="3"></textarea></label>
<label class="cvix-sig-check"><input type="checkbox" name="active" value="1" checked> Ativo</label>
<button type="submit" class="cvix-sig-btn">Salvar template</button>
</form>
<table class="cvix-sig-table"><thead><tr><th>Nome</th><th>Título</th><th>Ativo</th></tr></thead>
<tbody><?php foreach ($templates as $t): ?><tr><td><?php echo CentralVixSignSecurity::e($t->name); ?></td><td><?php echo CentralVixSignSecurity::e($t->title); ?></td><td><?php echo $t->active?'Sim':'Não'; ?></td></tr><?php endforeach; ?></tbody></table>
