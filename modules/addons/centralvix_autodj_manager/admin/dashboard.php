<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-adj-cards">
    <div class="cvix-adj-card"><span>Total contas</span><strong><?php echo (int)$stats['accounts']; ?></strong></div>
    <div class="cvix-adj-card ok"><span>AutoDJ ativos</span><strong><?php echo (int)$stats['active']; ?></strong></div>
    <div class="cvix-adj-card"><span>Arquivos</span><strong><?php echo (int)$stats['files']; ?></strong></div>
    <div class="cvix-adj-card info"><span>Espaço utilizado</span><strong><?php echo CentralVixAutoDJSecurity::e($stats['used_gb']); ?> GB</strong></div>
    <div class="cvix-adj-card warn"><span>Problemas (7d)</span><strong><?php echo (int)$stats['problems']; ?></strong></div>
</div>
<div class="cvix-adj-panel"><h3>CentralVix AutoDJ</h3><p>Gerenciador de biblioteca, playlists e programação com providers desacoplados.</p></div>
