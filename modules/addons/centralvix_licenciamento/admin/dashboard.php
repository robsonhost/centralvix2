<?php $s = CentralVixLicLicenseService::dashboardStats(); ?>
<div class="cvix-stats-grid">
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-blue"><i class="fas fa-key"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Total de licenças</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['total'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-cyan"><i class="fas fa-check-circle"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Ativas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['active'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-gray"><i class="fas fa-pause-circle"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Suspensas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['suspended'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-royal"><i class="fas fa-clock"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Pendentes</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['pending'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-danger"><i class="fas fa-times-circle"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Expiradas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['expired'], 0, ',', '.'); ?></span>
        </div>
    </div>
</div>
<div class="cvix-card">
    <div class="cvix-card-header"><h3><i class="fas fa-link"></i> API de validação</h3></div>
    <div class="cvix-card-body">
        <p class="cvix-hint" style="margin:0;"><?php echo CentralVixLicSecurity::e(CentralVixLicModule::apiBaseUrl()); ?></p>
    </div>
</div>
