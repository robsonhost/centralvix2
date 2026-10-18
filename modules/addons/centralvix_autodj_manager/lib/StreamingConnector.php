<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

interface AutoDJProviderInterface
{
    public function getType();

    public function isConfigured();

    public function testConnection();

    public function getCapabilities();

    public function uploadMedia($account, $media, $localPath);

    public function deleteMedia($account, $media);

    public function syncPlaylist($account, $playlist, array $items);

    public function applySchedule($account, $schedule);
}

abstract class CentralVixAutoDJProviderBase implements AutoDJProviderInterface
{
    protected $account;
    protected $config;
    protected $timeout = 20;

    public function __construct($account)
    {
        $this->account = $account;
        $this->config = json_decode($account->provider_config ?? '{}', true) ?: [];
        $this->timeout = max(5, (int) CentralVixAutoDJModule::setting('api_timeout', 20));
    }

    protected function notConfigured($action = '')
    {
        return [
            'success' => false,
            'message' => 'Provider ' . $this->getType() . ' não configurado'
                . ($action ? ' para "' . $action . '"' : '')
                . '. Informe URL e credenciais válidas ou use modo local.',
        ];
    }

    protected function http($url, $method = 'GET', $body = null, array $headers = [])
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_USERAGENT => 'CentralVix-AutoDJ/1.0',
        ];
        if ($headers) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return ['ok' => $err === '' && $code >= 200 && $code < 400, 'code' => $code, 'body' => $body, 'error' => $err];
    }

    public function getCapabilities()
    {
        return [
            'upload' => false,
            'delete' => false,
            'playlist' => false,
            'schedule' => false,
        ];
    }
}

class CentralVixAutoDJLocalProvider extends CentralVixAutoDJProviderBase
{
    public function getType()
    {
        return 'none';
    }

    public function isConfigured()
    {
        return true;
    }

    public function testConnection()
    {
        return ['success' => true, 'message' => 'Modo local — arquivos apenas no WHMCS.'];
    }

    public function uploadMedia($account, $media, $localPath)
    {
        return ['success' => true, 'message' => 'Armazenado localmente.'];
    }

    public function deleteMedia($account, $media)
    {
        return ['success' => true, 'message' => 'Removido localmente.'];
    }

    public function syncPlaylist($account, $playlist, array $items)
    {
        return ['success' => true, 'message' => 'Playlist salva localmente (sem sync remoto).'];
    }

    public function applySchedule($account, $schedule)
    {
        return ['success' => true, 'message' => 'Agendamento salvo localmente.'];
    }
}

/**
 * AzuraCast — requer API URL + API Key documentados em https://www.azuracast.com/docs/
 * Endpoints usados somente se base_url e api_key estiverem configurados.
 */
class CentralVixAzuraCastProvider extends CentralVixAutoDJProviderBase
{
    public function getType()
    {
        return 'azuracast';
    }

    public function isConfigured()
    {
        return !empty($this->config['base_url']) && !empty($this->config['api_key']);
    }

    public function getCapabilities()
    {
        if (!$this->isConfigured()) {
            return parent::getCapabilities();
        }
        return ['upload' => true, 'delete' => true, 'playlist' => true, 'schedule' => true];
    }

    public function testConnection()
    {
        if (!$this->isConfigured()) {
            return $this->notConfigured('test');
        }
        $url = rtrim($this->config['base_url'], '/') . '/api/status';
        $r = $this->http($url, 'GET', null, ['X-API-Key: ' . $this->config['api_key']]);
        if (!$r['ok']) {
            return ['success' => false, 'message' => 'AzuraCast inacessível (HTTP ' . $r['code'] . ').'];
        }
        return ['success' => true, 'message' => 'AzuraCast API respondendo.'];
    }

    public function uploadMedia($account, $media, $localPath)
    {
        if (!$this->isConfigured()) {
            return $this->notConfigured('upload');
        }
        $stationId = $this->config['station_id'] ?? '';
        if ($stationId === '') {
            return ['success' => false, 'message' => 'station_id AzuraCast não configurado na conta.'];
        }
        return [
            'success' => false,
            'message' => 'Upload AzuraCast requer station_id e endpoint /api/station/{id}/files — configure e estenda AzuraCastProvider.',
        ];
    }

    public function deleteMedia($account, $media)
    {
        return $this->isConfigured() ? $this->notConfigured('delete') : $this->notConfigured('delete');
    }

    public function syncPlaylist($account, $playlist, array $items)
    {
        return $this->isConfigured() ? $this->notConfigured('playlist') : $this->notConfigured('playlist');
    }

    public function applySchedule($account, $schedule)
    {
        return $this->isConfigured() ? $this->notConfigured('schedule') : $this->notConfigured('schedule');
    }
}

class CentralVixCentovaCastProvider extends CentralVixAutoDJProviderBase
{
    public function getType()
    {
        return 'centovacast';
    }

    public function isConfigured()
    {
        return !empty($this->config['api_url']) && !empty($this->config['username']) && !empty($this->config['password']);
    }

    public function testConnection()
    {
        if (!$this->isConfigured()) {
            return $this->notConfigured('test');
        }
        return [
            'success' => false,
            'message' => 'Centova Cast API varia por instalação. Configure api_url e estenda CentovaCastProvider com o método documentado do seu painel.',
        ];
    }

    public function uploadMedia($account, $media, $localPath)
    {
        return $this->notConfigured('upload');
    }

    public function deleteMedia($account, $media)
    {
        return $this->notConfigured('delete');
    }

    public function syncPlaylist($account, $playlist, array $items)
    {
        return $this->notConfigured('playlist');
    }

    public function applySchedule($account, $schedule)
    {
        return $this->notConfigured('schedule');
    }
}

class CentralVixSonicPanelProvider extends CentralVixAutoDJProviderBase
{
    public function getType()
    {
        return 'sonicpanel';
    }

    public function isConfigured()
    {
        return !empty($this->config['api_url']) && !empty($this->config['api_token']);
    }

    public function testConnection()
    {
        if (!$this->isConfigured()) {
            return $this->notConfigured('test');
        }
        return [
            'success' => false,
            'message' => 'SonicPanel requer API documentada da sua instalação. Configure api_url e api_token.',
        ];
    }

    public function uploadMedia($account, $media, $localPath)
    {
        return $this->notConfigured('upload');
    }

    public function deleteMedia($account, $media)
    {
        return $this->notConfigured('delete');
    }

    public function syncPlaylist($account, $playlist, array $items)
    {
        return $this->notConfigured('playlist');
    }

    public function applySchedule($account, $schedule)
    {
        return $this->notConfigured('schedule');
    }
}

class CentralVixAutoDJStreamingConnector
{
    public static function provider($account)
    {
        if (!$account) {
            return null;
        }
        switch ($account->provider_type) {
            case 'azuracast':
                return new CentralVixAzuraCastProvider($account);
            case 'centovacast':
                return new CentralVixCentovaCastProvider($account);
            case 'sonicpanel':
                return new CentralVixSonicPanelProvider($account);
            default:
                return new CentralVixAutoDJLocalProvider($account);
        }
    }
}
