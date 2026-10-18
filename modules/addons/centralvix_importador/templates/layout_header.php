<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-module">
    <div class="cvix-header">
        <div class="cvix-header-inner">
            <div class="cvix-brand">
                <div class="cvix-logo">
                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="10" fill="#0B3D91"/>
                        <path d="M10 28L20 12L30 28H10Z" fill="#00B4D8" opacity="0.9"/>
                        <circle cx="20" cy="22" r="3" fill="#FFFFFF"/>
                    </svg>
                </div>
                <div class="cvix-brand-text">
                    <span class="cvix-brand-name">CentralVix</span>
                    <h1 class="cvix-title">Importador de Clientes</h1>
                    <p class="cvix-subtitle">Importe sua base de clientes de forma rápida, organizada e segura.</p>
                </div>
            </div>
        </div>
    </div>

    <nav class="cvix-nav">
        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=dashboard" class="cvix-nav-item <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=import" class="cvix-nav-item <?php echo $tab === 'import' ? 'active' : ''; ?>">
            <i class="fas fa-file-import"></i> Nova Importação
        </a>
        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=history" class="cvix-nav-item <?php echo $tab === 'history' ? 'active' : ''; ?>">
            <i class="fas fa-history"></i> Histórico
        </a>
        <a href="<?php echo htmlspecialchars($modulelink); ?>&tab=settings" class="cvix-nav-item <?php echo $tab === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Configurações
        </a>
    </nav>

    <div class="cvix-content">
