<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account): ?>
<p>Selecione uma conta em <a href="<?php echo CentralVixStreamingSecurity::e($moduleLink); ?>">Dashboard</a>.</p>
<?php return; endif;
$streamUrl = CentralVixStreamingService::streamUrl($account);
?>
<div class="cvix-st-client-panel">
    <h3>Player — <?php echo CentralVixStreamingSecurity::e($account->stream_username); ?></h3>
    <audio controls preload="none" style="width:100%;max-width:640px" src="<?php echo CentralVixStreamingSecurity::e($streamUrl); ?>">
        Seu navegador não suporta áudio HTML5.
    </audio>
    <p class="cvix-st-hint">URL: <?php echo CentralVixStreamingSecurity::e($streamUrl); ?></p>
</div>
<div class="cvix-st-client-panel">
    <h3>Dados para encoder (OBS, BUTT, etc.)</h3>
    <dl class="cvix-st-dl">
        <dt>Servidor</dt><dd><?php echo CentralVixStreamingSecurity::e($account->hostname); ?></dd>
        <dt>Porta</dt><dd><?php echo (int)$account->port; ?></dd>
        <dt>Mount / Stream ID</dt><dd><?php echo CentralVixStreamingSecurity::e($account->mountpoint ?: '/'); ?></dd>
        <dt>Usuário</dt><dd><?php echo CentralVixStreamingSecurity::e($account->stream_username); ?></dd>
        <dt>Senha</dt><dd>Use "Alterar senha" no dashboard se necessário — nunca exibida aqui.</dd>
        <dt>Bitrate sugerido</dt><dd><?php echo (int)$account->bitrate; ?> kbps</dd>
    </dl>
</div>
