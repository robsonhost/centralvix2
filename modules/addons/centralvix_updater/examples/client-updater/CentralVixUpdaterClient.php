<?php

/**
 * Cliente PHP para o CentralVix Atualizador — uso em sistemas distribuídos.
 * Não inclui segredos do servidor; apenas license_key quando exigida.
 */
class CentralVixUpdaterClient
{
    private $apiBase;
    private $productSlug;
    private $licenseKey;
    private $channel;
    private $timeout;

    public function __construct($apiBase, $productSlug, $licenseKey = '', $channel = 'stable', $timeout = 30)
    {
        $this->apiBase = rtrim($apiBase, '/');
        $this->productSlug = $productSlug;
        $this->licenseKey = $licenseKey;
        $this->channel = in_array($channel, ['stable', 'beta'], true) ? $channel : 'stable';
        $this->timeout = (int) $timeout;
    }

    public function checkUpdate($currentVersion, $domain = '')
    {
        $payload = [
            'product' => $this->productSlug,
            'current_version' => $currentVersion,
            'channel' => $this->channel,
        ];
        if ($this->licenseKey !== '') {
            $payload['license_key'] = $this->licenseKey;
        }
        if ($domain !== '') {
            $payload['domain'] = $domain;
        }

        return $this->request('POST', '/check.php', $payload);
    }

    public function downloadPackage(array $downloadInfo, $destPath)
    {
        if (empty($downloadInfo['url']) || empty($downloadInfo['token'])) {
            throw new RuntimeException('Informações de download inválidas.');
        }

        $url = $downloadInfo['url'];
        $body = json_encode(['token' => $downloadInfo['token']]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => $this->timeout * 4,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $data = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($data === false) {
            throw new RuntimeException('Falha no download: ' . $error);
        }

        if ($code !== 200) {
            $json = json_decode($data, true);
            $msg = is_array($json) ? ($json['message'] ?? 'HTTP ' . $code) : 'HTTP ' . $code;
            throw new RuntimeException('Download recusado: ' . $msg);
        }

        $dir = dirname($destPath);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new RuntimeException('Não foi possível criar diretório de destino.');
            }
        }

        if (file_put_contents($destPath, $data, LOCK_EX) === false) {
            throw new RuntimeException('Falha ao gravar pacote.');
        }

        return $destPath;
    }

    public function verifySha256($filePath, $expectedHex)
    {
        if (!is_file($filePath)) {
            return false;
        }
        $hash = hash_file('sha256', $filePath);
        return hash_equals(strtolower($expectedHex), strtolower($hash));
    }

    public function verifyResponseSignature($body, $signature)
    {
        if ($signature === '' || $this->licenseKey === '') {
            return $signature === '' ? true : false;
        }
        $key = hash('sha256', 'cvix-upd-response-v1|' . $this->licenseKey);
        $expected = hash_hmac('sha256', $body, $key);
        return hash_equals($expected, $signature);
    }

    private function request($method, $path, array $payload)
    {
        $url = $this->apiBase . $path;
        $body = json_encode($payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $raw = curl_exec($ch);
        if ($raw === false) {
            throw new RuntimeException('Erro de conexão: ' . curl_error($ch));
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $headerStr = substr($raw, 0, $headerSize);
        $responseBody = substr($raw, $headerSize);
        $signature = '';

        foreach (explode("\r\n", $headerStr) as $line) {
            if (stripos($line, 'X-CVIX-Signature:') === 0) {
                $signature = trim(substr($line, 18));
            }
        }

        if (!$this->verifyResponseSignature($responseBody, $signature)) {
            throw new RuntimeException('Assinatura da resposta inválida.');
        }

        $json = json_decode($responseBody, true);
        if (!is_array($json)) {
            throw new RuntimeException('Resposta JSON inválida.');
        }

        $json['_http_code'] = $code;
        return $json;
    }
}

/**
 * Orquestrador de atualização com backup e rollback transacional.
 */
class CentralVixUpdateInstaller
{
    private $targetDir;
    private $backupDir;
    private $maintenanceFile;

    public function __construct($targetDir, $backupDir = null, $maintenanceFile = null)
    {
        $this->targetDir = rtrim($targetDir, '/\\');
        $this->backupDir = $backupDir ?: $this->targetDir . '/../backups';
        $this->maintenanceFile = $maintenanceFile ?: $this->targetDir . '/.maintenance';
    }

    public function createBackup($label = '')
    {
        $name = 'backup_' . date('Ymd_His') . ($label ? '_' . preg_replace('/[^a-z0-9_-]/i', '', $label) : '');
        $dest = rtrim($this->backupDir, '/\\') . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        if (!is_dir($this->targetDir)) {
            throw new RuntimeException('Diretório alvo inexistente.');
        }
        self::copyRecursive($this->targetDir, $dest);
        return $dest;
    }

    public function enableMaintenance($message = 'Atualização em andamento.')
    {
        file_put_contents($this->maintenanceFile, $message);
    }

    public function disableMaintenance()
    {
        if (is_file($this->maintenanceFile)) {
            unlink($this->maintenanceFile);
        }
    }

    /**
     * Extrai ZIP com proteção ZIP Slip. Rollback restaura backup em falha.
     */
    public function applyZipUpdate($zipPath, $backupPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP inválido.');
        }

        $staging = $this->targetDir . '._cvix_staging_' . bin2hex(random_bytes(4));
        mkdir($staging, 0755, true);

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if ($entry === false || substr($entry, -1) === '/') {
                    continue;
                }
                if (strpos($entry, '../') !== false || strpos($entry, '..\\') !== false || $entry[0] === '/') {
                    throw new RuntimeException('ZIP Slip detectado: ' . $entry);
                }
                $out = $staging . DIRECTORY_SEPARATOR . $entry;
                $outDir = dirname($out);
                if (!is_dir($outDir)) {
                    mkdir($outDir, 0755, true);
                }
                $stream = $zip->getStream($entry);
                if (!$stream) {
                    throw new RuntimeException('Falha ao ler entrada: ' . $entry);
                }
                file_put_contents($out, stream_get_contents($stream));
                fclose($stream);
            }
            $zip->close();

            self::mergeDirectory($staging, $this->targetDir);
            self::removeRecursive($staging);
        } catch (Exception $e) {
            $zip->close();
            self::removeRecursive($staging);
            $this->rollback($backupPath);
            throw $e;
        }
    }

    public function rollback($backupPath)
    {
        if (!is_dir($backupPath)) {
            throw new RuntimeException('Backup não encontrado para rollback.');
        }
        self::removeRecursive($this->targetDir);
        self::copyRecursive($backupPath, $this->targetDir);
    }

    private static function copyRecursive($src, $dst)
    {
        mkdir($dst, 0755, true);
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $s = $src . DIRECTORY_SEPARATOR . $file;
            $d = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($s)) {
                self::copyRecursive($s, $d);
            } else {
                copy($s, $d);
            }
        }
        closedir($dir);
    }

    private static function mergeDirectory($src, $dst)
    {
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $s = $src . DIRECTORY_SEPARATOR . $file;
            $d = $dst . DIRECTORY_SEPARATOR . $file;
            if (is_dir($s)) {
                if (!is_dir($d)) {
                    mkdir($d, 0755, true);
                }
                self::mergeDirectory($s, $d);
            } else {
                $parent = dirname($d);
                if (!is_dir($parent)) {
                    mkdir($parent, 0755, true);
                }
                copy($s, $d);
            }
        }
        closedir($dir);
    }

    private static function removeRecursive($path)
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            self::removeRecursive($path . DIRECTORY_SEPARATOR . $item);
        }
        rmdir($path);
    }
}
