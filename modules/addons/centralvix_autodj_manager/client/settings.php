<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
if (!$account) { return; }
include __DIR__ . '/_nav.php';
$q = CentralVixAutoDJStorageService::quotaInfo($account);
$provider = CentralVixAutoDJStreamingConnector::provider($account);
$test = $provider ? $provider->testConnection() : null;
?>
<div class="cvix-adj-panel">
    <h3>Uso de espaço</h3>
    <div class="cvix-adj-quota large">
        <div class="bar" style="width:<?php echo min(100,(float)$q['percent']); ?>%"></div>
    </div>
    <ul class="cvix-adj-stats">
        <li>Utilizado: <strong><?php echo CentralVixAutoDJSecurity::e($q['used_mb']); ?> MB</strong></li>
        <li>Total: <strong><?php echo (int)$q['total_mb']; ?> MB</strong> (<?php echo round($q['total_mb']/1024,1); ?> GB)</li>
        <li>Disponível: <strong><?php echo CentralVixAutoDJSecurity::e($q['available_mb']); ?> MB</strong></li>
        <li>Percentual: <strong><?php echo CentralVixAutoDJSecurity::e($q['percent']); ?>%</strong></li>
    </ul>
</div>
<div class="cvix-adj-panel">
    <h3>Provider AutoDJ</h3>
    <p>Tipo: <?php echo CentralVixAutoDJSecurity::e(CentralVixAutoDJModule::providerTypes()[$account->provider_type] ?? $account->provider_type); ?></p>
    <?php if ($test): ?><p class="cvix-adj-badge <?php echo $test['success']?'ok':'warn'; ?>"><?php echo CentralVixAutoDJSecurity::e($test['message']); ?></p><?php endif; ?>
</div>
