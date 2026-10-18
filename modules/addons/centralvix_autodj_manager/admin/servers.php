<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-adj-panel">
    <h3>Providers AutoDJ</h3>
    <p>Integrações via <code>AutoDJProviderInterface</code>. Sem API configurada, o modo local armazena arquivos no WHMCS.</p>
    <table class="cvix-adj-table">
        <thead><tr><th>Provider</th><th>Status</th><th>Capacidades</th></tr></thead>
        <tbody>
        <?php foreach ($providers as $k => $label):
            $dummy = (object)['provider_type' => $k, 'provider_config' => '{}'];
            $p = CentralVixAutoDJStreamingConnector::provider($dummy);
            $caps = $p ? $p->getCapabilities() : [];
        ?>
        <tr>
            <td><?php echo CentralVixAutoDJSecurity::e($label); ?></td>
            <td><?php echo ($p && $p->isConfigured()) || $k === 'none' ? '<span class="cvix-adj-badge ok">Disponível</span>' : '<span class="cvix-adj-badge muted">Requer config</span>'; ?></td>
            <td><?php echo CentralVixAutoDJSecurity::e(implode(', ', array_keys(array_filter($caps)))); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
