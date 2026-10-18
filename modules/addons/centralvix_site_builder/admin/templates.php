<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$fields = CentralVixSiteBriefingService::STANDARD_FIELDS;
$custom = CentralVixSiteBriefingService::getCustomFieldsConfig();
?>
<h2>Templates de briefing</h2>
<p class="cvix-sb-muted">Campos padrão do briefing (somente leitura). Configure campos adicionais em Configurações.</p>
<table class="cvix-sb-table">
<thead><tr><th>Campo</th><th>Label</th></tr></thead>
<tbody>
<?php foreach ($fields as $k=>$l): ?><tr><td><code><?php echo CentralVixSiteSecurity::e($k); ?></code></td><td><?php echo CentralVixSiteSecurity::e($l); ?></td></tr><?php endforeach; ?>
<?php foreach ($custom as $c): ?><tr><td><code><?php echo CentralVixSiteSecurity::e($c['key']); ?></code></td><td><?php echo CentralVixSiteSecurity::e($c['label']); ?> <em>(custom)</em></td></tr><?php endforeach; ?>
</tbody>
</table>
<h3>Fluxo de status</h3>
<ol class="cvix-sb-flow">
<?php foreach (CentralVixSiteProjectService::STATUSES as $k=>$l): ?><li><?php echo CentralVixSiteSecurity::e($l); ?></li><?php endforeach; ?>
</ol>
