<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); }
$publicCategories = [
    'Hospedagem' => ['hosting'],
    'VPS' => ['vps'],
    'Streaming' => ['streaming_audio', 'streaming_video'],
    'Aplicações' => ['sites', 'api', 'database', 'dedicated'],
    'Pagamentos' => ['payment'],
    'Comunicações' => ['whatsapp', 'dns', 'custom'],
];
$overallClass = CentralVixStatusModule::statusBadgeClass($overall);
$overallLabel = $statuses[$overall] ?? $overall;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CentralVix — Status dos Serviços</title>
    <link rel="stylesheet" href="<?php echo CentralVixStatusSecurity::e($assetBase); ?>/css/status.css?v=<?php echo CentralVixStatusSecurity::e($ver); ?>">
</head>
<body class="cvix-public">
<header class="cvix-public-header">
    <div class="cvix-public-wrap">
        <div class="cvix-public-brand">
            <div class="cvix-public-logo"></div>
            <div>
                <strong>CentralVix</strong>
                <h1>Status dos Serviços</h1>
            </div>
        </div>
        <div class="cvix-public-overall <?php echo CentralVixStatusSecurity::e($overallClass); ?>">
            <span class="cvix-public-dot"></span>
            <?php echo CentralVixStatusSecurity::e($overallLabel); ?>
        </div>
    </div>
    <p class="cvix-public-sub"><?php echo CentralVixStatusSecurity::e($message); ?></p>
</header>

<main class="cvix-public-wrap">
    <?php foreach ($publicCategories as $sectionLabel => $catKeys): ?>
        <?php
        $sectionServices = [];
        foreach ($catKeys as $ck) {
            if (!empty($grouped[$ck])) {
                $sectionServices = array_merge($sectionServices, $grouped[$ck]);
            }
        }
        if (empty($sectionServices)) {
            continue;
        }
        ?>
        <section class="cvix-public-section">
            <h2><?php echo CentralVixStatusSecurity::e($sectionLabel); ?></h2>
            <div class="cvix-public-services">
                <?php foreach ($sectionServices as $service): ?>
                <article class="cvix-public-service">
                    <div class="cvix-public-service-head">
                        <span class="cvix-public-indicator <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::statusBadgeClass($service->status)); ?>"></span>
                        <div>
                            <h3><?php echo CentralVixStatusSecurity::e($service->name); ?></h3>
                            <?php if ($service->description): ?>
                            <p><?php echo CentralVixStatusSecurity::e($service->description); ?></p>
                            <?php endif; ?>
                        </div>
                        <span class="cvix-public-status-label"><?php echo CentralVixStatusSecurity::e($statuses[$service->status] ?? $service->status); ?></span>
                    </div>
                    <div class="cvix-public-meta">
                        <span>Uptime 30d: <?php echo CentralVixStatusSecurity::e($service->uptime_percentage); ?>%</span>
                        <?php if ($service->response_time): ?>
                        <span>Resposta: <?php echo (int) $service->response_time; ?>ms</span>
                        <?php endif; ?>
                    </div>
                    <?php
                    $history = CentralVixStatusUptimeService::getChartData($service->id, '7d');
                    if ($history->count() > 0):
                    ?>
                    <div class="cvix-public-history" title="Histórico 7 dias">
                        <?php foreach ($history as $h): ?>
                        <span class="cvix-public-bar <?php echo in_array($h->status, ['operational', 'resolved'], true) ? 'ok' : 'fail'; ?>"></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <?php if (count($incidents)): ?>
    <section class="cvix-public-section">
        <h2>Incidentes recentes</h2>
        <div class="cvix-public-incidents">
            <?php foreach ($incidents as $inc): ?>
            <a class="cvix-public-incident" href="incident.php?id=<?php echo (int) $inc->id; ?>">
                <span class="cvix-public-badge <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::statusBadgeClass($inc->status)); ?>">
                    <?php echo CentralVixStatusSecurity::e($statuses[$inc->status] ?? $inc->status); ?>
                </span>
                <strong><?php echo CentralVixStatusSecurity::e($inc->title); ?></strong>
                <time><?php echo date('d/m/Y H:i', strtotime($inc->started_at)); ?></time>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if (count($maintenance)): ?>
    <section class="cvix-public-section">
        <h2>Manutenções programadas</h2>
        <div class="cvix-public-maintenance">
            <?php foreach ($maintenance as $m): ?>
            <article class="cvix-public-maint-item">
                <h3><?php echo CentralVixStatusSecurity::e($m->title); ?></h3>
                <?php if ($m->description): ?><p><?php echo CentralVixStatusSecurity::e($m->description); ?></p><?php endif; ?>
                <time><?php echo date('d/m/Y H:i', strtotime($m->start_at)); ?> — <?php echo date('d/m/Y H:i', strtotime($m->end_at)); ?></time>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<footer class="cvix-public-footer">
    <div class="cvix-public-wrap">
        <p>CentralVix Status Page — Atualizado em <?php echo date('d/m/Y H:i'); ?></p>
    </div>
</footer>
<script src="<?php echo CentralVixStatusSecurity::e($assetBase); ?>/js/status.js?v=<?php echo CentralVixStatusSecurity::e($ver); ?>"></script>
</body>
</html>
