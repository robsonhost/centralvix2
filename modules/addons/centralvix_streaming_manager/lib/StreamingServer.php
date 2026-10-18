<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

abstract class CentralVixStreamingProviderBase
{
    protected $server;
    protected $timeout = 15;

    public function __construct($server)
    {
        $this->server = $server;
        $this->timeout = max(5, (int) CentralVixStreamingModule::setting('api_timeout', 15));
    }

    abstract public function getType();

    abstract public function testConnection();

    abstract public function getLiveStats($account);

    public function getCapabilities()
    {
        return [
            'create' => false,
            'suspend' => false,
            'unsuspend' => false,
            'terminate' => false,
            'change_password' => false,
            'change_bitrate' => false,
            'change_listeners' => false,
            'start' => false,
            'stop' => false,
            'restart' => false,
        ];
    }

    public function createAccount($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function suspendAccount($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function unsuspendAccount($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function terminateAccount($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function changePassword($account, $newPassword)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function changeBitrate($account, $bitrate)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function changeMaxListeners($account, $max)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function startStream($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function stopStream($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function restartStream($account)
    {
        return $this->unsupported(__FUNCTION__);
    }

    protected function unsupported($operation)
    {
        return [
            'success' => false,
            'message' => 'A operação "' . $operation . '" não é suportada pela API '
                . strtoupper($this->getType()) . '. Configure provisionamento externo ou painel compatível.',
        ];
    }

    protected function baseUrl($port = null)
    {
        $scheme = !empty($this->server->use_ssl) ? 'https' : 'http';
        $host = $this->server->hostname;
        $p = $port ?: (int) $this->server->api_port;
        return $scheme . '://' . $host . ':' . $p;
    }

    protected function httpRequest($url, $method = 'GET', $body = null, array $headers = [], $user = null, $pass = null)
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_USERAGENT => 'CentralVix-StreamingManager/1.0',
        ];
        if ($user !== null) {
            $opts[CURLOPT_USERPWD] = $user . ':' . ($pass ?? '');
        }
        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        return [
            'success' => $err === '' && $code >= 200 && $code < 400,
            'http_code' => $code,
            'body' => $response,
            'error' => $err,
        ];
    }

    protected function getAdminPassword()
    {
        return CentralVixStreamingModule::decryptSecret($this->server->api_password_enc);
    }

    protected function getAdminUser()
    {
        return $this->server->api_user ?: 'admin';
    }

    protected function matchMount($sources, $mount)
    {
        $mount = $mount ?: '/stream';
        if ($mount[0] !== '/') {
            $mount = '/' . $mount;
        }
        foreach ((array) $sources as $src) {
            $listenurl = $src['listenurl'] ?? ($src['server_name'] ?? '');
            $listenurl = is_string($listenurl) ? $listenurl : '';
            if (isset($src['listenurl']) && strpos($src['listenurl'], $mount) !== false) {
                return $src;
            }
            if (isset($src['mount']) && ($src['mount'] === $mount || $src['mount'] === ltrim($mount, '/'))) {
                return $src;
            }
        }
        return is_array($sources) && count($sources) === 1 ? reset($sources) : null;
    }
}

class CentralVixStreamingServerFactory
{
    public static function provider($server)
    {
        if (!$server) {
            return null;
        }
        switch ($server->type) {
            case 'icecast':
                return new CentralVixIcecastProvider($server);
            case 'shoutcast':
                return new CentralVixShoutcastProvider($server);
            case 'video':
                return new CentralVixVideoProvider($server);
            default:
                return null;
        }
    }
}
