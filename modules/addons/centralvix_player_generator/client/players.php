<?php
if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$players = CentralVixPlayerService::listByClient($clientId);
$ajaxBase = CentralVixPlayerModule::ajaxUrl('', true);
?>
<div class="cvix-pg-client-header">
<h2>Meus Players</h2>
<a class="cvix-pg-btn" href="<?php echo CentralVixPlayerSecurity::e($modulelink . '&action=builder'); ?>">+ Novo player</a>
</div>
<div class="cvix-pg-player-grid">
<?php foreach ($players as $p): ?>
<div class="cvix-pg-player-card" data-id="<?php echo (int)$p->id; ?>">
<div class="cvix-pg-player-card-head">
<h3><?php echo CentralVixPlayerSecurity::e($p->radio_name); ?></h3>
<span class="cvix-pg-badge <?php echo $p->active ? 'ok' : 'off'; ?>"><?php echo $p->active ? 'Ativo' : 'Inativo'; ?></span>
</div>
<p class="cvix-pg-muted cvix-pg-stream"><?php echo CentralVixPlayerSecurity::e($p->stream_url); ?></p>
<div class="cvix-pg-embed-box">
<label>URL pública</label>
<input type="text" readonly class="cvix-pg-copy" value="<?php echo CentralVixPlayerSecurity::e(CentralVixPlayerEmbedService::publicUrl($p)); ?>">
<label>iframe</label>
<textarea readonly class="cvix-pg-copy cvix-pg-iframe-code" rows="3"><?php echo CentralVixPlayerSecurity::e(CentralVixPlayerEmbedService::iframeCode($p)); ?></textarea>
</div>
<div class="cvix-pg-actions">
<a href="<?php echo CentralVixPlayerSecurity::e($modulelink . '&action=builder&id=' . (int)$p->id); ?>" class="cvix-pg-btn sm">Editar</a>
<button type="button" class="cvix-pg-btn sm outline cvix-pg-dup" data-id="<?php echo (int)$p->id; ?>">Duplicar</button>
<a href="<?php echo CentralVixPlayerSecurity::e(CentralVixPlayerEmbedService::publicUrl($p)); ?>" target="_blank" class="cvix-pg-btn sm outline">Visualizar</a>
<button type="button" class="cvix-pg-btn sm outline cvix-pg-copy-btn">Copiar iframe</button>
<button type="button" class="cvix-pg-btn sm danger cvix-pg-del" data-id="<?php echo (int)$p->id; ?>">Excluir</button>
</div>
</div>
<?php endforeach; ?>
</div>
<?php if (count($players) === 0): ?>
<div class="cvix-pg-empty">Nenhum player criado. <a href="<?php echo CentralVixPlayerSecurity::e($modulelink . '&action=builder'); ?>">Criar agora</a></div>
<?php endif; ?>
<input type="hidden" id="cvix-pg-csrf" value="<?php echo CentralVixPlayerSecurity::e($csrf); ?>">
<input type="hidden" id="cvix-pg-ajax" value="<?php echo CentralVixPlayerSecurity::e($ajaxBase); ?>">
