<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-dashboard">
    <div class="cvix-stats-grid">
        <div class="cvix-stat-card">
            <div class="cvix-stat-icon cvix-stat-icon-blue">
                <i class="fas fa-database"></i>
            </div>
            <div class="cvix-stat-info">
                <span class="cvix-stat-label">Total de registros encontrados</span>
                <span class="cvix-stat-value"><?php echo number_format($stats['total_records'], 0, ',', '.'); ?></span>
            </div>
        </div>
        <div class="cvix-stat-card">
            <div class="cvix-stat-icon cvix-stat-icon-cyan">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="cvix-stat-info">
                <span class="cvix-stat-label">Total importado com sucesso</span>
                <span class="cvix-stat-value"><?php echo number_format($stats['total_imported'], 0, ',', '.'); ?></span>
            </div>
        </div>
        <div class="cvix-stat-card">
            <div class="cvix-stat-icon cvix-stat-icon-gray">
                <i class="fas fa-user-slash"></i>
            </div>
            <div class="cvix-stat-info">
                <span class="cvix-stat-label">Total ignorado</span>
                <span class="cvix-stat-value"><?php echo number_format($stats['total_skipped'], 0, ',', '.'); ?></span>
            </div>
        </div>
        <div class="cvix-stat-card">
            <div class="cvix-stat-icon cvix-stat-icon-royal">
                <i class="fas fa-user-edit"></i>
            </div>
            <div class="cvix-stat-info">
                <span class="cvix-stat-label">Total atualizado</span>
                <span class="cvix-stat-value"><?php echo number_format($stats['total_updated'], 0, ',', '.'); ?></span>
            </div>
        </div>
        <div class="cvix-stat-card">
            <div class="cvix-stat-icon cvix-stat-icon-danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="cvix-stat-info">
                <span class="cvix-stat-label">Total com erro</span>
                <span class="cvix-stat-value"><?php echo number_format($stats['total_errors'], 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>

    <div class="cvix-card">
        <div class="cvix-card-header">
            <h3><i class="fas fa-bolt"></i> Ações rápidas</h3>
        </div>
        <div class="cvix-card-body">
            <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=import" class="cvix-btn cvix-btn-primary cvix-btn-lg">
                <i class="fas fa-file-upload"></i> Iniciar nova importação
            </a>
            <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=history" class="cvix-btn cvix-btn-secondary cvix-btn-lg">
                <i class="fas fa-list"></i> Ver histórico completo
            </a>
        </div>
    </div>

    <?php if (!empty($recentImports['items']) && count($recentImports['items'])): ?>
    <div class="cvix-card">
        <div class="cvix-card-header">
            <h3><i class="fas fa-clock"></i> Importações recentes</h3>
        </div>
        <div class="cvix-card-body cvix-table-responsive">
            <table class="cvix-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Arquivo</th>
                        <th>Total</th>
                        <th>Importados</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentImports['items'] as $item): ?>
                    <tr>
                        <td>#<?php echo (int) $item->id; ?></td>
                        <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item->created_at))); ?></td>
                        <td><?php echo htmlspecialchars($item->filename); ?></td>
                        <td><?php echo (int) $item->total; ?></td>
                        <td><?php echo (int) $item->imported; ?></td>
                        <td><span class="cvix-badge cvix-badge-<?php echo htmlspecialchars($item->status); ?>"><?php echo htmlspecialchars(ucfirst($item->status)); ?></span></td>
                        <td>
                            <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $item->id; ?>" class="cvix-btn cvix-btn-sm cvix-btn-outline">
                                Ver detalhes
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
