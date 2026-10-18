<?php $s = CentralVixMigMigrationService::dashboardStats(); ?>
<div class="cvix-stats-grid">
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-blue"><i class="fas fa-folder-open"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Abertas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['open'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-cyan"><i class="fas fa-spinner"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Em andamento</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['in_progress'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-gray"><i class="fas fa-user-clock"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Aguardando cliente</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['waiting_client'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-royal"><i class="fas fa-calendar-check"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Agendadas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['scheduled'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-cyan"><i class="fas fa-check-double"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Concluídas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['completed'], 0, ',', '.'); ?></span>
        </div>
    </div>
    <div class="cvix-stat-card">
        <div class="cvix-stat-icon cvix-stat-icon-danger"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="cvix-stat-info">
            <span class="cvix-stat-label">Problemas</span>
            <span class="cvix-stat-value"><?php echo number_format((int) $s['problems'], 0, ',', '.'); ?></span>
        </div>
    </div>
</div>
<div class="cvix-card">
    <div class="cvix-card-header"><h3><i class="fas fa-info-circle"></i> Resumo</h3></div>
    <div class="cvix-card-body">
        <p class="cvix-hint" style="margin:0;">Total: <?php echo number_format((int) $s['total'], 0, ',', '.'); ?> solicitações · Provider: <?php echo CentralVixMigSecurity::e(CentralVixMigModule::migrationProvider()->getName()); ?></p>
    </div>
</div>
