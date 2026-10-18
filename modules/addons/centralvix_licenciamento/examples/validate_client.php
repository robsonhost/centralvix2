<?php
/**
 * Exemplo de cliente PHP — CentralVix Licenciamento
 *
 * IMPORTANTE: Embute apenas a LICENSE_KEY no seu sistema.
 * NÃO inclua api_secret, server_pepper ou segredos do servidor.
 *
 * A verificação de assinatura usa a própria license_key (via HMAC derivado).
 * Isso permite detectar adulteração MITM da resposta sem expor segredos admin.
 */

class CentralVixLicenseClient
{
    private $apiBase;
    private $licenseKey;
    private $domain;
    private $cacheFile;
    private $lastResponse;
    private $lastSignature;

    public function __construct($apiBase, $licenseKey, $domain, $cacheFile = null)
    {
        $this->apiBase = rtrim($apiBase, '/');
        $this->licenseKey = $licenseKey;
        $this->domain = strtolower(trim($domain));
        $this->cacheFile = $cacheFile ?: sys_get_temp_dir() . '/cvix_license_cache.json';
    }

    public function validate($forceRemote = false)
    {
        if (!$forceRemote && ($cached = $this->readCache())) {
            return $cached;
        }

        $response = $this->request('/validate.php', [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
        ]);

        if (!$this->verifySignature($response['body'], $response['signature'])) {
            return ['valid' => false, 'message' => 'Assinatura da resposta inválida.', 'from_cache' => false];
        }

        $data = json_decode($response['body'], true);
        if (!is_array($data) || empty($data['success'])) {
            $grace = $this->readCache(true);
            if ($grace) {
                $grace['from_cache'] = true;
                $grace['grace'] = true;
                return $grace;
            }
            return ['valid' => false, 'message' => $data['data']['message'] ?? 'Licença inválida.', 'from_cache' => false];
        }

        $payload = $data['data'];
        $payload['from_cache'] = false;
        $payload['cached_at'] = time();
        $this->writeCache($payload);
        return $payload;
    }

    public function activate()
    {
        $response = $this->request('/activate.php', [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
        ]);
        if (!$this->verifySignature($response['body'], $response['signature'])) {
            return ['success' => false, 'message' => 'Assinatura inválida.'];
        }
        return json_decode($response['body'], true);
    }

    protected function request($endpoint, array $payload)
    {
        $ch = curl_init($this->apiBase . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HEADER => true,
        ]);
        $raw = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headers = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);
        $signature = '';
        if (preg_match('/X-CVIX-Signature:\s*(.+)/i', $headers, $m)) {
            $signature = trim($m[1]);
        }

        $this->lastResponse = $body;
        $this->lastSignature = $signature;

        return ['body' => $body, 'signature' => $signature];
    }

    /**
     * Verifica HMAC da resposta.
     * O servidor deriva a chave de: SHA256(license_key + pepper_servidor + 'response')
     * O cliente NÃO possui pepper — portanto verifica offline apenas integridade
     * comparando campos críticos do cache local após primeira validação bem-sucedida HTTPS.
     *
     * Para verificação completa da assinatura, use o helper abaixo quando o servidor
     * expõe endpoint público de verificação ou documente o pepper por produto (não recomendado).
     */
    public function verifySignature($body, $signature)
    {
        if ($signature === '' || $body === '') {
            return false;
        }
        $expected = hash_hmac('sha256', $body, hash('sha256', 'cvix-response-v1|' . $this->licenseKey));
        return hash_equals($expected, $signature);
    }

    protected function readCache($allowGrace = false)
    {
        if (!is_file($this->cacheFile)) {
            return null;
        }
        $data = json_decode(file_get_contents($this->cacheFile), true);
        if (!is_array($data) || empty($data['cached_at'])) {
            return null;
        }
        $graceHours = (int) ($data['grace_period_hours'] ?? 24);
        $maxAge = $allowGrace ? ($graceHours * 3600) : 3600;
        if (time() - (int) $data['cached_at'] > $maxAge) {
            return null;
        }
        return $data;
    }

    protected function writeCache(array $data)
    {
        @file_put_contents($this->cacheFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}

// --- Uso ---
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
    $apiBase = 'https://seudominio.com.br/modules/addons/centralvix_licenciamento/api';
    $licenseKey = getenv('CVIX_LICENSE_KEY') ?: 'CVIX-XXXXXX-XXXXXX-XXXXXX-XXXXXX';
    $domain = getenv('CVIX_DOMAIN') ?: 'meusite.com.br';

    $client = new CentralVixLicenseClient($apiBase, $licenseKey, $domain);
    $result = $client->validate();

    if (!empty($result['valid'])) {
        echo "Licença VÁLIDA até " . ($result['expires_at'] ?? '?') . PHP_EOL;
        exit(0);
    }
    echo "Licença INVÁLIDA: " . ($result['message'] ?? 'erro') . PHP_EOL;
    exit(1);
}
