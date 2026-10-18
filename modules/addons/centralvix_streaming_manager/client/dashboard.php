<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$accounts || count($accounts) === 0): ?>
<div class="cvix-st-client-empty">
    <h3>Nenhum streaming ativo</h3>
    <p>Você ainda não possui contas de streaming vinculadas.</p>
</div>
<?php return; endif;
$account = $account ?? $accounts->first();
$live = $account ? CentralVixStreamingStatisticsService::collectForAccount($account->id) : null;
$streamUrl = $account ? CentralVixStreamingService::streamUrl($account) : '';
$provider = $account ? CentralVixStreamingService::providerForAccount($account) : null;
$caps = $provider ? $provider->getCapabilities() : [];
?>
<div class="cvix-st-client-nav">
    <?php foreach ($accounts as $a): ?>
    <a href="<?php echo CentralVixStreamingSecurity::e($moduleLink.'&page=dashboard&account='.$a->id); ?>" class="<?php echo ($account && $account->id==$a->id)?'active':''; ?>">
        <?php echo CentralVixStreamingSecurity::e($a->stream_username); ?>
    </a>
    <?php endforeach; ?>
    <a href="<?php echo CentralVixStreamingSecurity::e($moduleLink.'&page=player&account='.(int)$account->id); ?>">Player</a>
    <a href="<?php echo CentralVixStreamingSecurity::e($moduleLink.'&page=statistics&account='.(int)$account->id); ?>">Estatísticas</a>
</div>

<div class="cvix-st-client-grid">
    <div class="cvix-st-client-card <?php echo !empty($live['online'])?'online':'offline'; ?>">
        <span>Status</span>
        <strong><?php echo !empty($live['online']) ? 'Online' : 'Offline'; ?></strong>
    </div>
    <div class="cvix-st-client-card"><span>Ouvintes</span><strong><?php echo (int)($live['listeners'] ?? 0); ?></strong></div>
    <div class="cvix-st-client-card"><span>Pico</span><strong><?php echo (int)($live['peak_listeners'] ?? 0); ?></strong></div>
    <div class="cvix-st-client-card"><span>Bitrate</span><strong><?php echo (int)($live['bitrate'] ?? $account->bitrate); ?> kbps</strong></div>
</div>

<div class="cvix-st-client-panel">
    <h3>Detalhes da conta</h3>
    <dl class="cvix-st-dl">
        <dt>Servidor</dt><dd><?php echo CentralVixStreamingSecurity::e($account->hostname ?? $account->server_name); ?></dd>
        <dt>Porta</dt><dd><?php echo (int)$account->port; ?></dd>
        <dt>Mountpoint</dt><dd><?php echo CentralVixStreamingSecurity::e($account->mountpoint); ?></dd>
        <dt>URL streaming</dt><dd><a href="<?php echo CentralVixStreamingSecurity::e($streamUrl); ?>" target="_blank"><?php echo CentralVixStreamingSecurity::e($streamUrl); ?></a></dd>
        <dt>Usuário encoder</dt><dd><?php echo CentralVixStreamingSecurity::e($account->stream_username); ?></dd>
        <dt>Senha encoder</dt><dd>•••••••• <em>(oculta por segurança)</em></dd>
        <dt>Limite ouvintes</dt><dd><?php echo (int)$account->max_listeners; ?></dd>
        <dt>AutoDJ</dt><dd><?php echo $account->autodj ? 'Sim' : 'Não'; ?></dd>
    </dl>
</div>

<?php if ($account): ?>
<div class="cvix-st-client-actions">
    <?php if (!empty($caps['start'])): ?><button type="button" class="cvix-st-btn primary cvix-client-action" data-account="<?php echo (int)$account->id; ?>" data-action="start">Iniciar</button><?php endif; ?>
    <?php if (!empty($caps['stop'])): ?><button type="button" class="cvix-st-btn cvix-client-action" data-account="<?php echo (int)$account->id; ?>" data-action="stop">Parar</button><?php endif; ?>
    <?php if (!empty($caps['restart'])): ?><button type="button" class="cvix-st-btn cvix-client-action" data-account="<?php echo (int)$account->id; ?>" data-action="restart">Reiniciar</button><?php endif; ?>
    <?php if (!empty($caps['change_password'])): ?><button type="button" class="cvix-st-btn cvix-client-action" data-account="<?php echo (int)$account->id; ?>" data-action="change_password">Alterar senha</button><?php endif; ?>
</div>
<input type="hidden" id="cvixClientCsrf" value="<?php echo CentralVixStreamingSecurity::e($csrfToken); ?>">
<input type="hidden" id="cvixClientAccount" value="<?php echo (int)$account->id; ?>">
<?php endif; ?>
