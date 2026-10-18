<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$themes = CentralVixPlayerThemeService::listThemes();
?>
<h2>Temas padrão</h2>
<table class="cvix-pg-table">
<thead><tr><th>Nome</th><th>Primária</th><th>Secundária</th><th>Modo</th><th>Padrão</th></tr></thead>
<tbody>
<?php foreach ($themes as $t): ?>
<tr>
<td><?php echo CentralVixPlayerSecurity::e($t->name); ?></td>
<td><span class="cvix-pg-swatch" style="background:<?php echo CentralVixPlayerSecurity::e($t->primary_color); ?>"></span> <?php echo CentralVixPlayerSecurity::e($t->primary_color); ?></td>
<td><span class="cvix-pg-swatch" style="background:<?php echo CentralVixPlayerSecurity::e($t->secondary_color); ?>"></span> <?php echo CentralVixPlayerSecurity::e($t->secondary_color); ?></td>
<td><?php echo CentralVixPlayerSecurity::e($t->theme_mode); ?></td>
<td><?php echo $t->is_default ? 'Sim' : '—'; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
