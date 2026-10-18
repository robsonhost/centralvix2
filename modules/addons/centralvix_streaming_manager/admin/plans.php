<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-st-panel">
    <h3>Planos / Produtos WHMCS</h3>
    <p>Associe produtos a defaults de streaming (servidor, bitrate, ouvintes). Usado no provisionamento automático via hooks.</p>
    <form method="post" action="<?php echo CentralVixStreamingSecurity::e($modulelink.'&tab=plans'); ?>">
        <input type="hidden" name="token" value="<?php echo CentralVixStreamingSecurity::e($csrfToken); ?>">
        <input type="hidden" name="cvix_save_plans" value="1">
        <textarea name="plans_json" rows="12" class="cvix-st-code"><?php
        echo CentralVixStreamingSecurity::e(json_encode($plans ?: [[
            'product_id' => 0,
            'server_id' => 0,
            'bitrate' => 128,
            'max_listeners' => 100,
            'mountpoint' => '/stream',
            'port' => 8000,
            'disk_space_mb' => 512,
            'autodj' => false,
        ]], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        ?></textarea>
        <p class="cvix-st-hint">JSON array. Campos: product_id, server_id, bitrate, max_listeners, mountpoint, port, disk_space_mb, autodj</p>
        <button type="submit" class="cvix-st-btn primary">Salvar planos</button>
    </form>
</div>
<div class="cvix-st-panel">
    <h3>Produtos disponíveis</h3>
    <ul class="cvix-st-list">
        <?php foreach ($products as $p): ?>
        <li>#<?php echo (int)$p->id; ?> — <?php echo CentralVixStreamingSecurity::e($p->name); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
