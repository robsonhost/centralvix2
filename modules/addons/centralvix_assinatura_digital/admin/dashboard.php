<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$s = CentralVixSignDocumentService::dashboardStats();
?>
<h2>Dashboard</h2>
<div class="cvix-sig-cards">
<div class="cvix-sig-card warn"><span>Pendentes</span><strong><?php echo (int)$s['pending']; ?></strong></div>
<div class="cvix-sig-card ok"><span>Assinados</span><strong><?php echo (int)$s['signed']; ?></strong></div>
<div class="cvix-sig-card danger"><span>Recusados</span><strong><?php echo (int)$s['rejected']; ?></strong></div>
<div class="cvix-sig-card"><span>Expirados</span><strong><?php echo (int)$s['expired']; ?></strong></div>
</div>
