<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account): ?>
<div class="cvix-adj-empty"><h3>AutoDJ não configurado</h3><p>Sua conta AutoDJ ainda não foi provisionada. Contate o suporte CentralVix.</p></div>
<?php return; endif;
include __DIR__ . '/_nav.php';
?>
<div class="cvix-adj-client-hero">
    <h2><?php echo CentralVixAutoDJSecurity::e($account->name); ?></h2>
    <span class="cvix-adj-badge ok"><?php echo CentralVixAutoDJSecurity::e($account->status); ?></span>
</div>
<div class="cvix-adj-cards">
    <div class="cvix-adj-card"><span>Arquivos</span><strong><?php echo (int)$dash['media_count']; ?></strong></div>
    <div class="cvix-adj-card"><span>Playlists</span><strong><?php echo (int)$dash['playlists']; ?></strong></div>
    <div class="cvix-adj-card"><span>Agendamentos</span><strong><?php echo (int)$dash['schedules']; ?></strong></div>
    <div class="cvix-adj-card info"><span>Espaço</span><strong><?php echo CentralVixAutoDJSecurity::e($dash['quota']['percent']); ?>%</strong></div>
</div>
<div class="cvix-adj-quota">
    <div class="bar" style="width:<?php echo min(100, (float)$dash['quota']['percent']); ?>%"></div>
    <p><?php echo CentralVixAutoDJSecurity::e($dash['quota']['used_mb']); ?> MB de <?php echo (int)$dash['quota']['total_mb']; ?> MB · <?php echo CentralVixAutoDJSecurity::e($dash['quota']['available_mb']); ?> MB disponível</p>
</div>
