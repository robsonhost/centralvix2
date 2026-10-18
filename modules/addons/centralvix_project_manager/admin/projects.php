<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$projects = CentralVixPmProjectService::listAll();
$clients = WHMCS\Database\Capsule::table('tblclients')->select('id','firstname','lastname')->orderBy('firstname')->get();
$admins = WHMCS\Database\Capsule::table('tbladmins')->select('id','firstname','lastname')->get();
?>
<h2>Projetos</h2>
<?php if ($success): ?><div class="cvix-pm-alert ok"><?php echo CentralVixPmSecurity::e($success); ?></div><?php endif; ?>
<div class="cvix-pm-split">
<div class="cvix-pm-panel">
<h3>Novo projeto</h3>
<form method="post" class="cvix-pm-form">
<input type="hidden" name="csrf_token" value="<?php echo CentralVixPmSecurity::e($csrf); ?>">
<input type="hidden" name="create_project" value="1">
<label>Cliente<select name="client_id" required><option value="">—</option><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo CentralVixPmSecurity::e($c->firstname.' '.$c->lastname); ?></option><?php endforeach; ?></select></label>
<label>Nome<input type="text" name="project_name" required maxlength="150"></label>
<label>Tipo<select name="project_type"><?php foreach (CentralVixPmProjectService::TYPES as $k=>$l): ?><option value="<?php echo $k; ?>"><?php echo CentralVixPmSecurity::e($l); ?></option><?php endforeach; ?></select></label>
<label>Descrição<textarea name="description" rows="2"></textarea></label>
<label>Service ID<input type="number" name="service_id" min="0" value="0"></label>
<label>Invoice ID<input type="number" name="invoice_id" min="0" value="0"></label>
<label>Ticket ID<input type="number" name="ticket_id" min="0" value="0"></label>
<label>Responsável<select name="assigned_admin"><option value="0">—</option><?php foreach ($admins as $a): ?><option value="<?php echo (int)$a->id; ?>"><?php echo CentralVixPmSecurity::e($a->firstname.' '.$a->lastname); ?></option><?php endforeach; ?></select></label>
<label>Início<input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></label>
<label>Prazo<input type="date" name="deadline"></label>
<label class="cvix-pm-check"><input type="checkbox" name="client_visible" value="1" checked> Visível ao cliente</label>
<button type="submit" class="cvix-pm-btn">Criar</button>
</form>
</div>
<div class="cvix-pm-panel wide">
<table class="cvix-pm-table">
<thead><tr><th>Projeto</th><th>Cliente</th><th>Tipo</th><th>Status</th><th>%</th><th>Prazo</th><th></th></tr></thead>
<tbody>
<?php foreach ($projects as $p): ?>
<tr class="<?php echo CentralVixPmProjectService::isOverdue($p)?'overdue':''; ?>">
<td><?php echo CentralVixPmSecurity::e($p->project_name); ?></td>
<td><?php echo CentralVixPmSecurity::e($p->client_name); ?></td>
<td><?php echo CentralVixPmSecurity::e(CentralVixPmProjectService::typeLabel($p->project_type)); ?></td>
<td><?php echo CentralVixPmSecurity::e(CentralVixPmProjectService::statusLabel($p->status)); ?></td>
<td><?php echo (int)$p->progress_percent; ?>%</td>
<td><?php echo $p->deadline ? date('d/m/Y', strtotime($p->deadline)) : '—'; ?></td>
<td><a href="<?php echo CentralVixPmSecurity::e($modulelink.'&tab=project&id='.(int)$p->id); ?>">Abrir</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
