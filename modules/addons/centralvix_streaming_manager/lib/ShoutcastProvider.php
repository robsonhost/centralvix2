<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * SHOUTcast DNAS — stats via admin.cgi?mode=viewxml (API admin SC2) ou 7.html (legacy).
 * Referência: documentação SHOUTcast DNAS admin interface.
 */
class CentralVixShoutcastProvider extends CentralVixStreamingProviderBase
{
    public function getType()
    {
        return 'shoutcast';
    }

    public function getCapabilities()
    {
        $caps = parent::getCapabilities();
        if (!empty($this->server->api_password_enc)) {
            $caps['stop'] = true;
            $caps['restart'] = true;
        }
        if (!empty($this->server->provisioning_url)) {
            $caps['create'] = true;
            $caps['suspend'] = true;
            $caps['unsuspend'] = true;
            $caps['terminate'] = true;
            $caps['change_password'] = true;
            $caps['change_bitrate'] = true;
            $caps['change_listeners'] = true;
            $caps['start'] = true;
        }
        return $caps;
    }

    public function testConnection()
    {
        if (!empty($this->server->api_password_enc)) {
            $pass = urlencode($this->getAdminPassword());
            $url = $this->baseUrl() . '/admin.cgi?mode=viewxml&pass=' . $pass . '&sid=1';
            $res = $this->httpRequest($url);
            if ($res['success'] && stripos($res['body'], '<SHOUTCASTSERVER') !== false) {
                return ['success' => true, 'message' => 'SHOUTcast admin.cgi respondendo (viewxml).'];
            }
        }
        $legacy = $this->baseUrl() . '/7.html';
        $res = $this->httpRequest($legacy);
        if ($res['success'] && $res['body'] !== '') {
            return ['success' => true, 'message' => 'SHOUTcast respondendo (7.html — stats legacy).'];
        }
        return [
            'success' => false,
            'message' => 'Servidor SHOUTcast inacessível. Configure senha admin para API completa.',
        ];
    }

    public function getLiveStats($account)
    {
        if (!empty($this->server->api_password_enc)) {
            return $this->statsFromXml($account);
        }
        return $this->statsFromLegacy($account);
    }

    private function statsFromXml($account)
    {
        $pass = urlencode($this->getAdminPassword());
        $sid = max(1, (int) ($account->port ?: 1));
        $url = $this->baseUrl() . '/admin.cgi?mode=viewxml&pass=' . $pass . '&sid=' . $sid;
        $res = $this->httpRequest($url);
        if (!$res['success']) {
            return $this->offlineStats($account);
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($res['body']);
        if (!$xml) {
            return $this->offlineStats($account);
        }
        $online = (string) ($xml->ONLINE ?? '0') === '1';
        return [
            'online' => $online,
            'listeners' => (int) ($xml->CURRENTLISTENERS ?? 0),
            'peak_listeners' => (int) ($xml->PEAKLISTENERS ?? 0),
            'bitrate' => (int) ($xml->BITRATE ?? $account->bitrate),
            'maxlisteners' => (int) ($xml->MAXLISTENERS ?? $account->max_listeners),
            'server_name' => (string) ($xml->SERVERTITLE ?? ''),
        ];
    }

    private function statsFromLegacy($account)
    {
        $url = $this->baseUrl((int) ($account->port ?: $this->server->api_port)) . '/7.html';
        $res = $this->httpRequest($url);
        if (!$res['success']) {
            return $this->offlineStats($account);
        }
        $parts = explode(',', strip_tags($res['body']));
        if (count($parts) < 5) {
            return $this->offlineStats($account);
        }
        return [
            'online' => ((int) trim($parts[1]) > 0) || ((int) trim($parts[0]) >= 0),
            'listeners' => (int) trim($parts[0]),
            'peak_listeners' => (int) trim($parts[3]),
            'bitrate' => (int) trim($parts[5] ?? $account->bitrate),
        ];
    }

    private function offlineStats($account)
    {
        return [
            'online' => false,
            'listeners' => 0,
            'peak_listeners' => 0,
            'bitrate' => (int) $account->bitrate,
            'message' => 'Offline',
        ];
    }

    public function stopStream($account)
    {
        if (empty($this->server->api_password_enc)) {
            return ['success' => false, 'message' => 'Senha admin SHOUTcast não configurada.'];
        }
        return $this->adminCommand('killsrc', $account);
    }

    public function restartStream($account)
    {
        if (empty($this->server->api_password_enc)) {
            return ['success' => false, 'message' => 'Senha admin SHOUTcast não configurada.'];
        }
        return $this->adminCommand('restart', $account);
    }

    private function adminCommand($mode, $account)
    {
        $pass = urlencode($this->getAdminPassword());
        $sid = max(1, (int) ($account->port ?: 1));
        $url = $this->baseUrl() . '/admin.cgi?mode=' . $mode . '&pass=' . $pass . '&sid=' . $sid;
        $res = $this->httpRequest($url);
        if (!$res['success']) {
            return [
                'success' => false,
                'message' => 'Comando admin.cgi "' . $mode . '" falhou (HTTP ' . $res['http_code'] . ').',
            ];
        }
        return ['success' => true, 'message' => 'Comando "' . $mode . '" enviado ao SHOUTcast.'];
    }

    public function createAccount($account)
    {
        return $this->webhook('create', $account);
    }

    public function suspendAccount($account)
    {
        $w = $this->webhook('suspend', $account);
        return $w['success'] ? $w : $this->stopStream($account);
    }

    public function unsuspendAccount($account)
    {
        return $this->webhook('unsuspend', $account);
    }

    public function terminateAccount($account)
    {
        return $this->webhook('terminate', $account);
    }

    public function changePassword($account, $newPassword)
    {
        return $this->webhook('change_password', $account, ['password' => $newPassword]);
    }

    public function changeBitrate($account, $bitrate)
    {
        return $this->webhook('change_bitrate', $account, ['bitrate' => (int) $bitrate]);
    }

    public function changeMaxListeners($account, $max)
    {
        return $this->webhook('change_listeners', $account, ['max_listeners' => (int) $max]);
    }

    public function startStream($account)
    {
        return $this->webhook('start', $account);
    }

    private function webhook($action, $account, array $extra = [])
    {
        if (empty($this->server->provisioning_url)) {
            return $this->unsupported($action);
        }
        $payload = json_encode(array_merge([
            'action' => $action,
            'account_id' => $account->id,
            'username' => $account->stream_username,
            'port' => $account->port,
        ], $extra));
        $headers = ['Content-Type: application/json'];
        $token = CentralVixStreamingModule::decryptSecret($this->server->api_password_enc);
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $res = $this->httpRequest($this->server->provisioning_url, 'POST', $payload, $headers);
        if (!$res['success']) {
            return ['success' => false, 'message' => 'Webhook SHOUTcast falhou (HTTP ' . $res['http_code'] . ').'];
        }
        return ['success' => true, 'message' => 'Ação delegada ao painel de provisionamento.'];
    }
}
