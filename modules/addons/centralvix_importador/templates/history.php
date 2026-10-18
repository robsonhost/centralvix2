<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
$statusLabels = [
    'pending' => 'Pendente',
    'analyzed' => 'Analisado',
    'ready' => 'Pronto',
    'processing' => 'Processando',
    'completed' => 'Concluído',
    'failed' => 'Falhou',
];
$totalPages = ceil($history['total'] / $history['per_page']);
?>
<div class="cvix-card">
    <div class="cvix-card-header">
        <h3><i class="fas fa-history"></i> Histórico de importações</h3>
        <span class="cvix-badge"><?php echo (int) $history['total']; ?> registros</span>
    </div>
    <div class="cvix-card-body cvix-table-responsive">
        <?php if (empty($history['items']) || !count($history['items'])): ?>
            <div class="cvix-empty-state">
                <i class="fas fa-inbox"></i>
                <p>Nenhuma importação realizada ainda.</p>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=import" class="cvix-btn cvix-btn-primary">Iniciar primeira importação</a>
            </div>
        <?php else: ?>
        <table class="cvix-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Arquivo</th>
                    <th>Administrador</th>
                    <th>Total</th>
                    <th>Importados</th>
                    <th>Atualizados</th>
                    <th>Ignorados</th>
                    <th>Erros</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history['items'] as $item): ?>
                <tr>
                    <td>#<?php echo (int) $item->id; ?></td>
                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item->created_at))); ?></td>
                    <td><?php echo htmlspecialchars($item->filename); ?></td>
                    <td><?php echo htmlspecialchars(CentralVixImportadorHelper::getAdminName($item->admin_id)); ?></td>
                    <td><?php echo (int) $item->total; ?></td>
                    <td><span class="cvix-text-success"><?php echo (int) $item->imported; ?></span></td>
                    <td><span class="cvix-text-info"><?php echo (int) $item->updated; ?></span></td>
                    <td><?php echo (int) $item->skipped; ?></td>
                    <td><span class="cvix-text-danger"><?php echo (int) $item->errors; ?></span></td>
                    <td>
                        <span class="cvix-badge cvix-badge-<?php echo htmlspecialchars($item->status); ?>">
                            <?php echo htmlspecialchars($statusLabels[$item->status] ?? ucfirst($item->status)); ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $item->id; ?>" class="cvix-btn cvix-btn-sm cvix-btn-outline">
                            <i class="fas fa-eye"></i> Ver detalhes
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="cvix-pagination">
            <?php if ($history['page'] > 1): ?>
            <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=history&page=<?php echo $history['page'] - 1; ?>" class="cvix-btn cvix-btn-sm cvix-btn-secondary">&laquo; Anterior</a>
            <?php endif; ?>
            <span>Página <?php echo (int) $history['page']; ?> de <?php echo (int) $totalPages; ?></span>
            <?php if ($history['page'] < $totalPages): ?>
            <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=history&page=<?php echo $history['page'] + 1; ?>" class="cvix-btn cvix-btn-sm cvix-btn-secondary">Próxima &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
