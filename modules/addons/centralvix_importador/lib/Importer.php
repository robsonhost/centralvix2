<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixImporter
{
    private $importId;
    private $reader;
    private $mapping;
    private $options;
    private $defaults;
    private $offset = 0;

    public function __construct($importId)
    {
        $this->importId = (int) $importId;
        $import = CentralVixImportLogger::getImport($this->importId);
        if (!$import) {
            throw new RuntimeException('Importação não encontrada.');
        }

        if (!file_exists($import->file_path)) {
            throw new RuntimeException('Arquivo de importação não encontrado.');
        }

        $ext = pathinfo($import->filename, PATHINFO_EXTENSION);
        $this->reader = new CentralVixSpreadsheetReader($import->file_path, $ext);
        $this->reader->analyze();

        $this->mapping = json_decode($import->mapping, true) ?: [];
        $this->options = json_decode($import->settings, true) ?: [];
        if (!isset($this->options['generate_missing_tax_id'])) {
            $this->options['generate_missing_tax_id'] = false;
        }
        if (!isset($this->options['allow_missing_tax_id'])) {
            $this->options['allow_missing_tax_id'] = true;
        }
        $this->defaults = [
            'currency' => $this->options['default_currency'] ?? '',
            'groupid' => $this->options['default_groupid'] ?? '',
            'language' => $this->options['default_language'] ?? 'portuguese-br',
            'country' => $this->options['default_country'] ?? 'BR',
        ];
        $this->offset = (int) ($import->current_offset ?? 0);
    }

    public function getTotalRows()
    {
        return $this->reader->getTotalRows();
    }

    public function getCurrentOffset()
    {
        return $this->offset;
    }

    public function processBatch($batchSize = null)
    {
        $batchSize = $batchSize ?: CentralVixImportadorHelper::BATCH_SIZE;
        $rows = $this->reader->getRowsBatch($this->offset, $batchSize);

        $service = new CentralVixClientService($this->options);
        $counters = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
        $results = [];

        foreach ($rows as $row) {
            $clientData = CentralVixClientMapper::applyMapping($row['data'], $this->mapping, $this->defaults);
            $result = $service->processClient($clientData, $row['row_number']);
            $results[] = $result;

            $status = $result['status'];
            if (isset($counters[$status])) {
                $counters[$status]++;
            } elseif ($status === 'error') {
                $counters['errors']++;
            }

            CentralVixImportLogger::logRow($this->importId, $result);
        }

        $processed = count($rows);
        $this->offset += $processed;

        CentralVixImportLogger::incrementCounters($this->importId, $counters);
        CentralVixImportLogger::updateImportMeta($this->importId, [
            'current_offset' => $this->offset,
        ]);

        $total = $this->reader->getTotalRows();
        $completed = $this->offset >= $total;

        if ($completed) {
            CentralVixImportLogger::finishImport($this->importId, 'completed');
            $this->cleanupFile();
        }

        $import = CentralVixImportLogger::getImport($this->importId);

        return [
            'processed' => $processed,
            'offset' => $this->offset,
            'total' => $total,
            'completed' => $completed,
            'counters' => [
                'imported' => (int) $import->imported,
                'updated' => (int) $import->updated,
                'skipped' => (int) $import->skipped,
                'errors' => (int) $import->errors,
            ],
            'batch_counters' => $counters,
            'results' => $results,
            'progress_percent' => $total > 0 ? round(($this->offset / $total) * 100, 1) : 100,
        ];
    }

    private function cleanupFile()
    {
        $import = CentralVixImportLogger::getImport($this->importId);
        if ($import && !empty($import->file_path) && file_exists($import->file_path)) {
            @unlink($import->file_path);
            CentralVixImportLogger::updateImportMeta($this->importId, ['file_path' => '']);
        }
    }

    public static function cleanupTempFiles($maxAgeHours = 24)
    {
        CentralVixImportadorHelper::ensureStorageDirs();
        $tempPath = CentralVixImportadorHelper::tempPath();
        $files = glob($tempPath . DIRECTORY_SEPARATOR . '*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > ($maxAgeHours * 3600)) {
                @unlink($file);
            }
        }
    }
}
