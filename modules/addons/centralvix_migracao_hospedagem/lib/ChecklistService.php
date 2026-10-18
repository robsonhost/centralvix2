<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigChecklistService
{
    public static function defaultItems()
    {
        return [
            'backup_origem' => 'Backup na origem',
            'arquivos' => 'Arquivos migrados',
            'bancos' => 'Bancos de dados',
            'cron' => 'Tarefas cron',
            'ssl' => 'Certificado SSL',
            'emails' => 'Contas de e-mail',
            'dns' => 'DNS apontado',
            'permissoes' => 'Permissões verificadas',
            'php' => 'Versão PHP compatível',
            'teste_site' => 'Teste do site',
            'teste_formulario' => 'Teste de formulário',
            'teste_email' => 'Teste de envio de e-mail',
            'finalizado' => 'Finalizado',
        ];
    }

    public static function initForRequest($requestId)
    {
        $existing = Capsule::table('mod_centralvix_migration_checklist')
            ->where('request_id', (int) $requestId)->count();
        if ($existing > 0) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        foreach (self::defaultItems() as $key => $label) {
            Capsule::table('mod_centralvix_migration_checklist')->insert([
                'request_id' => (int) $requestId,
                'item_key' => $key,
                'label' => $label,
                'completed' => 0,
                'created_at' => $now,
            ]);
        }
    }

    public static function listForRequest($requestId)
    {
        return Capsule::table('mod_centralvix_migration_checklist')
            ->where('request_id', (int) $requestId)
            ->orderBy('id')
            ->get();
    }

    public static function toggle($requestId, $itemKey, $completed, $adminId = 0, $notes = '')
    {
        $itemKey = preg_replace('/[^a-z_]/', '', $itemKey);
        $row = Capsule::table('mod_centralvix_migration_checklist')
            ->where('request_id', (int) $requestId)
            ->where('item_key', $itemKey)
            ->first();
        if (!$row) {
            return ['success' => false, 'message' => 'Item não encontrado.'];
        }
        Capsule::table('mod_centralvix_migration_checklist')->where('id', $row->id)->update([
            'completed' => $completed ? 1 : 0,
            'completed_at' => $completed ? date('Y-m-d H:i:s') : null,
            'completed_by' => $completed ? (int) $adminId : 0,
            'notes' => CentralVixMigSecurity::sanitizeText($notes, 500),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixMigLogger::log(
            $requestId,
            'checklist_' . ($completed ? 'done' : 'undone'),
            $row->label,
            ['item' => $itemKey],
            'admin',
            $adminId
        );
        self::syncProgress($requestId);
        return ['success' => true];
    }

    public static function syncProgress($requestId)
    {
        $items = self::listForRequest($requestId);
        $total = count($items);
        if ($total === 0) {
            return;
        }
        $done = 0;
        foreach ($items as $item) {
            if ($item->completed) {
                $done++;
            }
        }
        $percent = (int) round(($done / $total) * 100);
        Capsule::table('mod_centralvix_migration_requests')->where('id', (int) $requestId)->update([
            'progress_percent' => $percent,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function progressSummary($requestId)
    {
        $items = self::listForRequest($requestId);
        $done = 0;
        foreach ($items as $item) {
            if ($item->completed) {
                $done++;
            }
        }
        return ['done' => $done, 'total' => count($items)];
    }
}
