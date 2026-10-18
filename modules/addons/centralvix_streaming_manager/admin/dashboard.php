<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-st-cards">
    <div class="cvix-st-card"><span>Contas</span><strong><?php echo (int)$stats['accounts']; ?></strong></div>
    <div class="cvix-st-card ok"><span>Ativas</span><strong><?php echo (int)$stats['active']; ?></strong></div>
    <div class="cvix-st-card warn"><span>Suspensas</span><strong><?php echo (int)$stats['suspended']; ?></strong></div>
    <div class="cvix-st-card info"><span>Servidores</span><strong><?php echo (int)$stats['servers']; ?></strong></div>
    <div class="cvix-st-card"><span>Ouvintes (1h)</span><strong><?php echo (int)$stats['listeners']; ?></strong></div>
</div>
<div class="cvix-st-panel">
    <h3>Plataforma CentralVix Streaming</h3>
    <p>Gerencie servidores SHOUTcast, Icecast e prepare integrações de vídeo via providers desacoplados.</p>
</div>
