<?php if (!defined('WHMCS')) { die('This file cannot be accessed directly'); } ?>
<div class="cvix-card">
    <div class="cvix-card-header">
        <h3><i class="fas fa-cog"></i> Configurações do módulo</h3>
    </div>
    <div class="cvix-card-body">
        <div class="cvix-alert cvix-alert-info">
            <i class="fas fa-info-circle"></i>
            As configurações abaixo são gerenciadas em <strong>Configurações &gt; Addon Modules &gt; CentralVix Importador de Clientes</strong> no WHMCS.
        </div>

        <div class="cvix-settings-info">
            <div class="cvix-settings-row">
                <span>Tamanho máximo do arquivo</span>
                <strong><?php echo htmlspecialchars($settings['max_file_size'] ?? '10'); ?> MB</strong>
            </div>
            <div class="cvix-settings-row">
                <span>Clientes por lote</span>
                <strong><?php echo htmlspecialchars($settings['batch_size'] ?? '25'); ?></strong>
            </div>
            <div class="cvix-settings-row">
                <span>Moeda padrão</span>
                <strong><?php echo htmlspecialchars($settings['default_currency'] ?? 'BRL'); ?></strong>
            </div>
            <div class="cvix-settings-row">
                <span>País padrão</span>
                <strong><?php echo htmlspecialchars($settings['default_country'] ?? 'BR'); ?></strong>
            </div>
            <div class="cvix-settings-row">
                <span>Idioma padrão</span>
                <strong><?php echo htmlspecialchars($settings['default_language'] ?? 'portuguese-br'); ?></strong>
            </div>
            <div class="cvix-settings-row">
                <span>Grupo padrão (ID)</span>
                <strong><?php echo htmlspecialchars($settings['default_groupid'] ?? '—'); ?></strong>
            </div>
            <div class="cvix-settings-row">
                <span>Limpeza automática</span>
                <strong><?php echo htmlspecialchars($settings['auto_cleanup_hours'] ?? '24'); ?> horas</strong>
            </div>
        </div>

        <div class="cvix-card cvix-card-nested">
            <div class="cvix-card-header">
                <h4><i class="fas fa-book"></i> Dependências</h4>
            </div>
            <div class="cvix-card-body">
                <?php if ($spreadsheetAvailable): ?>
                <div class="cvix-alert cvix-alert-success">
                    <i class="fas fa-check-circle"></i> PhpSpreadsheet instalado — suporte a Excel (.xlsx/.xls) ativo.
                </div>
                <?php else: ?>
                <div class="cvix-alert cvix-alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    PhpSpreadsheet <strong>não instalado</strong>. CSV funciona normalmente.
                    <br>Para Excel, via SSH na pasta do módulo:
                    <code>rm -rf vendor composer.lock && composer install --no-dev</code>
                    <br><small>Não use PhpSpreadsheet 1.29.x — bloqueado por segurança. Use a 2.x do composer.json.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="cvix-card cvix-card-nested">
            <div class="cvix-card-header">
                <h4><i class="fas fa-shield-alt"></i> Segurança</h4>
            </div>
            <div class="cvix-card-body">
                <ul class="cvix-security-list">
                    <li><i class="fas fa-check"></i> Utiliza localAPI (AddClient / UpdateClient) — sem INSERT SQL direto</li>
                    <li><i class="fas fa-check"></i> Validação CSRF em todas as requisições AJAX</li>
                    <li><i class="fas fa-check"></i> Validação de sessão de administrador</li>
                    <li><i class="fas fa-check"></i> Validação de MIME type e extensão de arquivo</li>
                    <li><i class="fas fa-check"></i> Pasta temporária protegida via .htaccess</li>
                    <li><i class="fas fa-check"></i> Não executa DELETE, não altera faturas, serviços ou domínios</li>
                    <li><i class="fas fa-check"></i> Senhas nunca registradas em logs</li>
                </ul>
            </div>
        </div>
    </div>
</div>
