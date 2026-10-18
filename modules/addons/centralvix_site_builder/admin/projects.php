<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$projects = CentralVixSiteProjectService::listAll(['status' => $_GET['filter_status'] ?? '']);
$clients = WHMCS\Database\Capsule::table('tblclients')->select('id', 'firstname', 'lastname')->orderBy('firstname')->get();
$admins = WHMCS\Database\Capsule::table('tbladmins')->select('id', 'firstname', 'lastname')->get();
?>
<h2>Projetos</h2>
<?php if (!empty($success)): ?><div class="cvix-sb-alert ok"><?php echo CentralVixSiteSecurity::e($success); ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="cvix-sb-alert err"><?php echo CentralVixSiteSecurity::e($error); ?></div><?php endif; ?>

<div class="cvix-sb-split">
<div class="cvix-sb-panel">
<h3>Novo projeto</h3>
<form method="post" class="cvix-sb-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixSiteSecurity::e($csrf); ?>">
<input type="hidden" name="create_project" value="1">
<label>Cliente<select name="client_id" required><option value="">—</option>
<?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo CentralVixSiteSecurity::e($c->firstname.' '.$c->lastname); ?></option><?php endforeach; ?>
</select></label>
<label>Nome do projeto<input type="text" name="project_name" required maxlength="150"></label>
<label>Produto/serviço<input type="text" name="product_name" maxlength="150"></label>
<label>ID serviço WHMCS<input type="number" name="service_id" min="0" value="0"></label>
<label>Domínio<input type="text" name="domain" maxlength="150"></label>
<label>Plano<input type="text" name="plan" maxlength="100"></label>
<label>Responsável<select name="assigned_admin"><option value="0">—</option>
<?php foreach ($admins as $a): ?><option value="<?php echo (int)$a->id; ?>"><?php echo CentralVixSiteSecurity::e($a->firstname.' '.$a->lastname); ?></option><?php endforeach; ?>
</select></label>
<label>Início<input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></label>
<label>Prazo<input type="date" name="deadline"></label>
<button type="submit" class="cvix-sb-btn">Criar projeto</button>
</form>
</div>
<div class="cvix-sb-panel wide">
<table class="cvix-sb-table">
<thead><tr><th>ID</th><th>Projeto</th><th>Cliente</th><th>Status</th><th>%</th><th>Prazo</th><th></th></tr></thead>
<tbody>
<?php foreach ($projects as $p): ?>
<tr class="<?php echo CentralVixSiteProjectService::isOverdue($p)?'overdue':''; ?>">
<td><?php echo (int)$p->id; ?></td>
<td><?php echo CentralVixSiteSecurity::e($p->project_name); ?></td>
<td><?php echo CentralVixSiteSecurity::e($p->client_name); ?></td>
<td><?php echo CentralVixSiteSecurity::e(CentralVixSiteProjectService::statusLabel($p->status)); ?></td>
<td><?php echo (int)$p->progress_percent; ?>%</td>
<td><?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : '—'; ?></td>
<td><a href="<?php echo CentralVixSiteSecurity::e($modulelink.'&tab=project&id='.(int)$p->id); ?>">Gerenciar</a></td>
</tr>
<?php endforeach; ?>
<?php if (!count($projects)): ?><tr><td colspan="7">Nenhum projeto.</td></tr><?php endif; ?>
</tbody>
</table>
</div>
</div>
