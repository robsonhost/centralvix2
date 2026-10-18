<?php

/**
 * Demonstração do fluxo recomendado de atualização CentralVix.
 *
 * Por padrão roda em DRY_RUN (não altera produção).
 * Defina CVIX_APPLY=1 para executar backup + instalação real.
 *
 * Uso:
 *   php run_update.php
 */

require_once __DIR__ . '/CentralVixUpdaterClient.php';

$config = [
    'api_base' => getenv('CVIX_API_BASE') ?: 'https://seudominio.com/modules/addons/centralvix_updater/api',
    'product' => getenv('CVIX_PRODUCT') ?: 'meu-sistema',
    'license_key' => getenv('CVIX_LICENSE') ?: '',
    'channel' => getenv('CVIX_CHANNEL') ?: 'stable',
    'current_version' => getenv('CVIX_VERSION') ?: '1.0.0',
    'target_dir' => getenv('CVIX_TARGET') ?: __DIR__ . '/demo-app',
    'dry_run' => getenv('CVIX_APPLY') !== '1',
];

echo "=== CentralVix Update Client (demo) ===\n";
echo $config['dry_run'] ? "Modo: DRY_RUN (sem alterar produção)\n\n" : "Modo: APLICAR atualização\n\n";

$client = new CentralVixUpdaterClient(
    $config['api_base'],
    $config['product'],
    $config['license_key'],
    $config['channel']
);

echo "1) Consultar versão...\n";
try {
    $response = $client->checkUpdate($config['current_version'], gethostname());
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

if (empty($response['success'])) {
    echo "   Sem atualização ou acesso negado.\n";
    exit(0);
}

$data = $response['data'] ?? [];
if (empty($data['update_available'])) {
    echo "   Sistema já está na versão mais recente ({$config['current_version']}).\n";
    exit(0);
}

echo "   Nova versão: {$data['latest_version']}\n";
echo "   Obrigatória: " . (!empty($data['required']) ? 'sim' : 'não') . "\n";
echo "   PHP mínimo: " . ($data['minimum_php'] ?? '?') . "\n";

if (!empty($data['minimum_php']) && version_compare(PHP_VERSION, $data['minimum_php'], '<')) {
    echo "   ERRO: PHP " . PHP_VERSION . " abaixo do mínimo.\n";
    exit(1);
}

if ($config['dry_run']) {
    echo "\n[DRY_RUN] Parando antes do download. Defina CVIX_APPLY=1 para continuar.\n";
    echo "Release notes:\n" . strip_tags($data['release_notes'] ?? '') . "\n";
    exit(0);
}

$checksum = $data['checksum'] ?? [];
$download = $data['download'] ?? [];
$packagePath = sys_get_temp_dir() . '/cvix_update_' . bin2hex(random_bytes(8)) . '.zip';

echo "\n2) Baixar pacote...\n";
try {
    $client->downloadPackage($download, $packagePath);
    echo "   Salvo em: {$packagePath}\n";
} catch (Exception $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n3) Validar SHA-256...\n";
if (empty($checksum['sha256']) || !$client->verifySha256($packagePath, $checksum['sha256'])) {
    @unlink($packagePath);
    echo "   ERRO: checksum não confere.\n";
    exit(1);
}
echo "   Checksum OK.\n";

$installer = new CentralVixUpdateInstaller($config['target_dir']);

echo "\n4) Criar backup...\n";
try {
    $backup = $installer->createBackup($data['latest_version']);
    echo "   Backup: {$backup}\n";
} catch (Exception $e) {
    @unlink($packagePath);
    echo "   ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n5) Modo manutenção...\n";
$installer->enableMaintenance();
echo "   Ativado.\n";

echo "\n6) Aplicar atualização...\n";
try {
    $installer->applyZipUpdate($packagePath, $backup);
    echo "   Atualização concluída para v{$data['latest_version']}.\n";
} catch (Exception $e) {
    echo "\n7) Rollback por erro: " . $e->getMessage() . "\n";
    try {
        $installer->rollback($backup);
        echo "   Rollback concluído.\n";
    } catch (Exception $rb) {
        echo "   FALHA CRÍTICA no rollback: " . $rb->getMessage() . "\n";
    }
    $installer->disableMaintenance();
    @unlink($packagePath);
    exit(1);
}

$installer->disableMaintenance();
@unlink($packagePath);
echo "\nProcesso finalizado com sucesso.\n";
