<?php $s = CentralVixUpdVersionService::dashboardStats(); ?>
<div class="cvix-stats-grid">
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-blue"><i class="fas fa-box"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Produtos</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['products'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-cyan"><i class="fas fa-code-branch"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Versões</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['versions'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-royal"><i class="fas fa-download"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Downloads</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['downloads'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-gray"><i class="fas fa-sync"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Checks</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['updates'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-danger"><i class="fas fa-exclamation-circle"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Falhas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['failures'], 0, ',', '.'); ?></span>
        </div>
    </div>
</div>
<div class="cvix-card">
    <div class="cvix-card-header"><h3><i class="fas fa-info-circle"></i> Informações</h3></div>
    <div class="cvix-card-body">
        <p class="cvix-hint" style="margin:0 0 8px;">API: <?php echo CentralVixUpdSecurity::e(CentralVixUpdModule::apiBaseUrl()); ?></p>
        <p class="cvix-hint" style="margin:0;">Licenciamento integrado: <?php echo CentralVixUpdLicenseConnector::licenciamentoAvailable() ? 'Sim' : 'Não (modular)'; ?></p>
    </div>
</div>
<?php $tail = CentralVixUpdLogger::tailLog(15); if ($tail): ?>
<div class="cvix-card">
    <div class="cvix-card-header"><h3><i class="fas fa-list-alt"></i> Log recente</h3></div>
    <div class="cvix-card-body">
        <pre class="cvix-log"><?php echo CentralVixUpdSecurity::e(implode("\n", $tail)); ?></pre>
    </div>
</div>
<?php endif; ?>
