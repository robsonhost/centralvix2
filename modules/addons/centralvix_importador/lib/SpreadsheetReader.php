<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixSpreadsheetReader
{
    private $filepath;
    private $extension;
    private $headers = [];
    private $totalRows = 0;
    private $hasSpreadsheet = false;

    public function __construct($filepath, $extension)
    {
        $this->filepath = $filepath;
        $this->extension = strtolower($extension);
        $this->hasSpreadsheet = CentralVixImportadorHelper::loadSpreadsheetLibrary();
    }

    public function isSpreadsheetAvailable()
    {
        return $this->hasSpreadsheet || $this->extension === 'csv';
    }

    public function getSpreadsheetMissingMessage()
    {
        if ($this->extension === 'csv') {
            return '';
        }
        return CentralVixImportadorHelper::getExcelReadinessMessage();
    }

    public function analyze()
    {
        if ($this->extension === 'csv') {
            return $this->analyzeCsv();
        }
        if (!$this->hasSpreadsheet) {
            $msg = CentralVixImportadorHelper::getExcelReadinessMessage();
            throw new RuntimeException($msg ?: 'PhpSpreadsheet não disponível.');
        }
        return $this->analyzeExcel();
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getTotalRows()
    {
        return $this->totalRows;
    }

    public function getPreviewRows($limit = 10)
    {
        if ($this->extension === 'csv') {
            return $this->readCsvRows(1, $limit);
        }
        return $this->readExcelRows(1, $limit);
    }

    public function getRowsBatch($offset, $limit)
    {
        if ($this->extension === 'csv') {
            return $this->readCsvRows($offset, $limit);
        }
        return $this->readExcelRows($offset, $limit);
    }

    private function analyzeCsv()
    {
        $handle = fopen($this->filepath, 'r');
        if (!$handle) {
            throw new RuntimeException('Não foi possível abrir o arquivo CSV.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new RuntimeException('Arquivo CSV vazio.');
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);
        if (!$headerRow || !count(array_filter($headerRow))) {
            fclose($handle);
            throw new RuntimeException('Cabeçalho CSV inválido ou ausente.');
        }

        $this->headers = $this->normalizeHeaders($headerRow);
        $this->totalRows = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->rowHasData($row)) {
                $this->totalRows++;
            }
        }

        fclose($handle);
        return true;
    }

    private function analyzeExcel()
    {
        $missing = CentralVixImportadorHelper::getExcelReadinessMessage();
        if ($missing !== '') {
            throw new RuntimeException($missing);
        }

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($this->filepath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($this->filepath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();

            if ($highestRow < 1) {
                throw new RuntimeException('Planilha Excel vazia.');
            }

            $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false)[0];
            $this->headers = $this->normalizeHeaders($headerRow);

            $this->totalRows = 0;
            for ($row = 2; $row <= $highestRow; $row++) {
                $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false)[0];
                if ($this->rowHasData($rowData)) {
                    $this->totalRows++;
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (Throwable $e) {
            throw new RuntimeException('Erro ao ler Excel: ' . $e->getMessage());
        }

        return true;
    }

    private function readCsvRows($offset, $limit)
    {
        $handle = fopen($this->filepath, 'r');
        if (!$handle) {
            return [];
        }

        $firstLine = fgets($handle);
        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);
        fgetcsv($handle, 0, $delimiter);

        $rows = [];
        $current = 0;
        $dataIndex = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!$this->rowHasData($row)) {
                continue;
            }
            $current++;
            if ($current <= $offset) {
                continue;
            }
            if (count($rows) >= $limit) {
                break;
            }
            $dataIndex++;
            $rows[] = [
                'row_number' => $offset + $dataIndex + 1,
                'data' => $this->mapRowToHeaders($row),
            ];
        }

        fclose($handle);
        return $rows;
    }

    private function readExcelRows($offset, $limit)
    {
        if (!$this->hasSpreadsheet) {
            return [];
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($this->filepath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($this->filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $rows = [];
        $current = 0;
        $dataIndex = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, null, true, false)[0];
            if (!$this->rowHasData($rowData)) {
                continue;
            }
            $current++;
            if ($current <= $offset) {
                continue;
            }
            if (count($rows) >= $limit) {
                break;
            }
            $dataIndex++;
            $rows[] = [
                'row_number' => $row,
                'data' => $this->mapRowToHeaders($rowData),
            ];
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }

    private function mapRowToHeaders(array $row)
    {
        $mapped = [];
        foreach ($this->headers as $index => $header) {
            $mapped[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }
        return $mapped;
    }

    private function normalizeHeaders(array $headers)
    {
        $normalized = [];
        $used = [];
        foreach ($headers as $i => $header) {
            $name = trim((string) $header);
            if ($name === '') {
                $name = 'coluna_' . ($i + 1);
            }
            $base = $name;
            $counter = 1;
            while (in_array($name, $used, true)) {
                $name = $base . '_' . $counter;
                $counter++;
            }
            $used[] = $name;
            $normalized[] = $name;
        }
        return $normalized;
    }

    private function rowHasData(array $row)
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }
        return false;
    }

    private function detectCsvDelimiter($line)
    {
        $delimiters = [';' => 0, ',' => 0, "\t" => 0];
        foreach ($delimiters as $delimiter => &$count) {
            $count = substr_count($line, $delimiter);
        }
        arsort($delimiters);
        $keys = array_keys($delimiters);
        return $keys[0] ?: ',';
    }
}
