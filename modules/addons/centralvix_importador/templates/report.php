<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<?php
$statusLabels = [
    'imported' => 'Importado',
    'updated' => 'Atualizado',
    'skipped' => 'Ignorado',
    'error' => 'Erro',
];
$importStatusLabels = [
    'completed' => 'Concluído',
    'failed' => 'Falhou',
    'processing' => 'Processando',
];
$logTotalPages = ceil($logs['total'] / $logs['per_page']);
$csrfToken = CentralVixImportadorHelper::generateCsrfToken();
$downloadUrl = CentralVixImportadorHelper::getAdminAjaxUrl('report', [
    'report_action' => 'download_csv',
    'import_id' => (int) $import->id,
    'token' => $csrfToken,
]);
?>
<div class="cvix-report">
    <div class="cvix-card">
        <div class="cvix-card-header">
            <h3><i class="fas fa-file-alt"></i> Relatório — Importação #<?php echo (int) $import->id; ?></h3>
            <div class="cvix-header-actions">
                <a href="<?php echo htmlspecialchars($downloadUrl); ?>" class="cvix-btn cvix-btn-sm cvix-btn-secondary">
                    <i class="fas fa-download"></i> Baixar CSV
                </a>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=history" class="cvix-btn cvix-btn-sm cvix-btn-outline">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>
        <div class="cvix-card-body">
            <div class="cvix-report-summary">
                <div class="cvix-report-item"><span>Arquivo</span><strong><?php echo htmlspecialchars($import->filename); ?></strong></div>
                <div class="cvix-report-item"><span>Administrador</span><strong><?php echo htmlspecialchars($adminName); ?></strong></div>
                <div class="cvix-report-item"><span>Data</span><strong><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($import->created_at))); ?></strong></div>
                <div class="cvix-report-item"><span>Status</span><strong><?php echo htmlspecialchars($importStatusLabels[$import->status] ?? ucfirst($import->status)); ?></strong></div>
            </div>

            <div class="cvix-final-stats cvix-final-stats-inline">
                <div class="cvix-final-stat"><span>Total analisado</span><strong><?php echo (int) $import->total; ?></strong></div>
                <div class="cvix-final-stat"><span>Importados</span><strong class="cvix-text-success"><?php echo (int) $import->imported; ?></strong></div>
                <div class="cvix-final-stat"><span>Atualizados</span><strong class="cvix-text-info"><?php echo (int) $import->updated; ?></strong></div>
                <div class="cvix-final-stat"><span>Ignorados</span><strong><?php echo (int) $import->skipped; ?></strong></div>
                <div class="cvix-final-stat"><span>Erros</span><strong class="cvix-text-danger"><?php echo (int) $import->errors; ?></strong></div>
            </div>

            <div class="cvix-filter-bar">
                <span>Filtrar por status:</span>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $import->id; ?>" class="cvix-filter-tag <?php echo $statusFilter === '' ? 'active' : ''; ?>">Todos</a>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $import->id; ?>&status=imported" class="cvix-filter-tag <?php echo $statusFilter === 'imported' ? 'active' : ''; ?>">Importados</a>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $import->id; ?>&status=updated" class="cvix-filter-tag <?php echo $statusFilter === 'updated' ? 'active' : ''; ?>">Atualizados</a>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $import->id; ?>&status=skipped" class="cvix-filter-tag <?php echo $statusFilter === 'skipped' ? 'active' : ''; ?>">Ignorados</a>
                <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=report&import_id=<?php echo (int) $import->id; ?>&status=error" class="cvix-filter-tag <?php echo $statusFilter === 'error' ? 'active' : ''; ?>">Erros</a>
            </div>

            <div class="cvix-table-responsive">
                <table class="cvix-table">
                    <thead>
                        <tr>
                            <th>Linha</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Client ID</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs['logs']) || !count($logs['logs'])): ?>
                        <tr><td colspan="6" class="text-center">Nenhum registro encontrado.</td></tr>
                        <?php else: ?>
                        <?php foreach ($logs['logs'] as $log): ?>
                        <tr class="cvix-row-<?php echo htmlspecialchars($log->status); ?>">
                            <td><?php echo (int) $log->row_number; ?></td>
                            <td><?php echo htmlspecialchars($log->name); ?></td>
                            <td><?php echo htmlspecialchars($log->email); ?></td>
                            <td>
                                <span class="cvix-badge cvix-badge-log-<?php echo htmlspecialchars($log->status); ?>">
                                    <?php echo htmlspecialchars($statusLabels[$log->status] ?? $log->status); ?>
                                </span>
                            </td>
                            <td><?php echo $log->client_id ? (int) $log->client_id : '-'; ?></td>
                            <td><?php echo htmlspecialchars($log->message); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($logTotalPages > 1): ?>
            <div class="cvix-pagination">
                <?php
                $baseUrl = $modulelink . '&tab=report&import_id=' . (int) $import->id;
                if ($statusFilter) $baseUrl .= '&status=' . urlencode($statusFilter);
                ?>
                <?php if ($logs['page'] > 1): ?>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>&log_page=<?php echo $logs['page'] - 1; ?>" class="cvix-btn cvix-btn-sm cvix-btn-secondary">&laquo; Anterior</a>
                <?php endif; ?>
                <span>Página <?php echo (int) $logs['page']; ?> de <?php echo (int) $logTotalPages; ?> (<?php echo (int) $logs['total']; ?> registros)</span>
                <?php if ($logs['page'] < $logTotalPages): ?>
                <a href="<?php echo htmlspecialchars($baseUrl); ?>&log_page=<?php echo $logs['page'] + 1; ?>" class="cvix-btn cvix-btn-sm cvix-btn-secondary">Próxima &raquo;</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
