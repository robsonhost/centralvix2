<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo CentralVixStatusSecurity::e($incident->title); ?> — CentralVix Status</title>
    <link rel="stylesheet" href="<?php echo CentralVixStatusSecurity::e($assetBase); ?>/css/status.css?v=<?php echo CentralVixStatusSecurity::e($ver); ?>">
</head>
<body class="cvix-public cvix-incident-page">
<header class="cvix-public-header">
    <div class="cvix-public-wrap">
        <div class="cvix-public-brand">
            <div class="cvix-public-logo"></div>
            <div>
                <strong>CentralVix</strong>
                <h1>Incidente #<?php echo (int) $incident->id; ?></h1>
            </div>
        </div>
        <a href="status.php" class="cvix-public-back">← Voltar ao status</a>
    </div>
</header>

<main class="cvix-public-wrap">
    <article class="cvix-incident-detail">
        <span class="cvix-public-badge <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::statusBadgeClass($incident->status)); ?>">
            <?php echo CentralVixStatusSecurity::e($statuses[$incident->status] ?? $incident->status); ?>
        </span>
        <h2><?php echo CentralVixStatusSecurity::e($incident->title); ?></h2>
        <?php if ($incident->description): ?>
        <p class="cvix-incident-desc"><?php echo nl2br(CentralVixStatusSecurity::e($incident->description)); ?></p>
        <?php endif; ?>
        <p class="cvix-incident-meta">
            Início: <?php echo date('d/m/Y H:i', strtotime($incident->started_at)); ?>
            <?php if ($incident->resolved_at): ?>
            · Resolvido: <?php echo date('d/m/Y H:i', strtotime($incident->resolved_at)); ?>
            <?php endif; ?>
        </p>

        <div class="cvix-sp-timeline cvix-public-timeline">
            <h3>Timeline</h3>
            <?php foreach ($updates as $u): ?>
            <div class="cvix-sp-timeline-item">
                <span class="time"><?php echo date('d/m/Y H:i', strtotime($u->created_at)); ?></span>
                <span class="cvix-public-badge <?php echo CentralVixStatusSecurity::e(CentralVixStatusModule::statusBadgeClass($u->status)); ?>">
                    <?php echo CentralVixStatusSecurity::e($statuses[$u->status] ?? $u->status); ?>
                </span>
                <p><?php echo nl2br(CentralVixStatusSecurity::e($u->message)); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </article>
</main>

<footer class="cvix-public-footer">
    <div class="cvix-public-wrap"><p>CentralVix Status Page</p></div>
</footer>
</body>
</html>
