<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixImportLogger
{
    public static function createImport($adminId, $filename, $total)
    {
        $id = Capsule::table('mod_centralvix_importacoes')->insertGetId([
            'admin_id' => (int) $adminId,
            'filename' => $filename,
            'total' => (int) $total,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'status' => 'processing',
            'settings' => '',
            'mapping' => '',
            'file_path' => '',
            'created_at' => date('Y-m-d H:i:s'),
            'finished_at' => null,
        ]);
        return (int) $id;
    }

    public static function updateImportMeta($importId, array $data)
    {
        Capsule::table('mod_centralvix_importacoes')
            ->where('id', (int) $importId)
            ->update($data);
    }

    public static function incrementCounters($importId, array $counters)
    {
        $import = Capsule::table('mod_centralvix_importacoes')->where('id', (int) $importId)->first();
        if (!$import) {
            return;
        }
        Capsule::table('mod_centralvix_importacoes')->where('id', (int) $importId)->update([
            'imported' => (int) $import->imported + (int) ($counters['imported'] ?? 0),
            'updated' => (int) $import->updated + (int) ($counters['updated'] ?? 0),
            'skipped' => (int) $import->skipped + (int) ($counters['skipped'] ?? 0),
            'errors' => (int) $import->errors + (int) ($counters['errors'] ?? 0),
        ]);
    }

    public static function logRow($importId, array $rowResult)
    {
        Capsule::table('mod_centralvix_importacao_logs')->insert([
            'import_id' => (int) $importId,
            'row_number' => (int) ($rowResult['row_number'] ?? 0),
            'name' => self::sanitizeLogText($rowResult['name'] ?? ''),
            'email' => self::sanitizeLogText($rowResult['email'] ?? ''),
            'client_id' => (int) ($rowResult['client_id'] ?? 0),
            'status' => $rowResult['status'] ?? 'error',
            'message' => self::sanitizeLogText($rowResult['message'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function finishImport($importId, $status = 'completed')
    {
        Capsule::table('mod_centralvix_importacoes')->where('id', (int) $importId)->update([
            'status' => $status,
            'finished_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function getImport($importId)
    {
        return Capsule::table('mod_centralvix_importacoes')->where('id', (int) $importId)->first();
    }

    public static function getImportLogs($importId, $page = 1, $perPage = 50, $statusFilter = '')
    {
        $query = Capsule::table('mod_centralvix_importacao_logs')
            ->where('import_id', (int) $importId)
            ->orderBy('row_number', 'asc');

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $total = $query->count();
        $logs = $query->forPage($page, $perPage)->get();

        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'logs' => $logs,
        ];
    }

    public static function getHistory($page = 1, $perPage = 20)
    {
        try {
            if (!Capsule::schema()->hasTable('mod_centralvix_importacoes')) {
                return [
                    'total' => 0,
                    'page' => $page,
                    'per_page' => $perPage,
                    'items' => [],
                ];
            }

            $query = Capsule::table('mod_centralvix_importacoes')->orderBy('id', 'desc');
            $total = $query->count();
            $items = $query->forPage($page, $perPage)->get();

            return [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'items' => $items,
            ];
        } catch (Throwable $e) {
            return [
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'items' => [],
            ];
        }
    }

    public static function exportCsv($importId)
    {
        $logs = Capsule::table('mod_centralvix_importacao_logs')
            ->where('import_id', (int) $importId)
            ->orderBy('row_number', 'asc')
            ->get();

        $statusLabels = [
            'imported' => 'Importado',
            'updated' => 'Atualizado',
            'skipped' => 'Ignorado',
            'error' => 'Erro',
        ];

        $lines = [];
        $lines[] = ['linha', 'nome', 'email', 'status', 'client_id', 'mensagem'];

        foreach ($logs as $log) {
            $lines[] = [
                $log->row_number,
                $log->name,
                $log->email,
                isset($statusLabels[$log->status]) ? $statusLabels[$log->status] : $log->status,
                $log->client_id,
                $log->message,
            ];
        }

        return $lines;
    }

    private static function sanitizeLogText($text)
    {
        $text = (string) $text;
        $text = preg_replace('/senha\s*[:=]\s*\S+/iu', 'senha: [oculta]', $text);
        $text = preg_replace('/password\s*[:=]\s*\S+/iu', 'password: [oculta]', $text);
        return mb_substr($text, 0, 500);
    }
}
