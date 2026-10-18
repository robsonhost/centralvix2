<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Icecast — stats via /status-json.xsl (documentado oficialmente).
 * Admin: autenticação HTTP em /admin/*. Operações limitadas sem painel externo.
 */
class CentralVixIcecastProvider extends CentralVixStreamingProviderBase
{
    public function getType()
    {
        return 'icecast';
    }

    public function getCapabilities()
    {
        $caps = parent::getCapabilities();
        $caps['stop'] = !empty($this->server->api_password_enc);
        $caps['restart'] = false;
        $caps['start'] = false;
        if (!empty($this->server->provisioning_url)) {
            $caps['create'] = true;
            $caps['suspend'] = true;
            $caps['unsuspend'] = true;
            $caps['terminate'] = true;
            $caps['change_password'] = true;
            $caps['change_bitrate'] = true;
            $caps['change_listeners'] = true;
        }
        return $caps;
    }

    public function testConnection()
    {
        $url = $this->baseUrl() . '/status-json.xsl';
        $res = $this->httpRequest($url);
        if (!$res['success']) {
            return [
                'success' => false,
                'message' => 'Não foi possível acessar status-json.xsl: HTTP '
                    . $res['http_code'] . ($res['error'] ? ' — ' . $res['error'] : ''),
            ];
        }
        $json = json_decode($res['body'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Resposta JSON inválida do Icecast.'];
        }
        return ['success' => true, 'message' => 'Icecast respondendo corretamente (status-json.xsl).'];
    }

    public function getLiveStats($account)
    {
        $url = $this->baseUrl((int) ($account->port ?: $this->server->api_port)) . '/status-json.xsl';
        $res = $this->httpRequest($url);
        if (!$res['success']) {
            return [
                'online' => false,
                'listeners' => 0,
                'peak_listeners' => 0,
                'bitrate' => (int) $account->bitrate,
                'message' => 'Offline ou indisponível',
            ];
        }
        $data = json_decode($res['body'], true);
        $sources = $data['icestats']['source'] ?? [];
        if (isset($sources['listenurl'])) {
            $sources = [$sources];
        }
        $mount = $account->mountpoint ?: '/stream';
        $src = $this->matchMount($sources, $mount);
        if (!$src) {
            return [
                'online' => false,
                'listeners' => 0,
                'peak_listeners' => 0,
                'bitrate' => (int) $account->bitrate,
                'message' => 'Mount não encontrado',
            ];
        }
        return [
            'online' => true,
            'listeners' => (int) ($src['listeners'] ?? 0),
            'peak_listeners' => (int) ($src['listener_peak'] ?? $src['listeners'] ?? 0),
            'bitrate' => (int) ($src['bitrate'] ?? $account->bitrate),
            'server_description' => $src['server_description'] ?? '',
            'stream_start' => $src['stream_start_iso'] ?? ($src['stream_start'] ?? null),
        ];
    }

    public function stopStream($account)
    {
        if (empty($this->server->api_password_enc)) {
            return [
                'success' => false,
                'message' => 'Credencial admin Icecast não configurada. Não é possível derrubar source.',
            ];
        }
        $mount = rawurlencode(ltrim($account->mountpoint ?: 'stream', '/'));
        $url = $this->baseUrl() . '/admin/killsource.xsl?mount=/' . $mount;
        $res = $this->httpRequest($url, 'GET', null, [], $this->getAdminUser(), $this->getAdminPassword());
        if (!$res['success']) {
            return [
                'success' => false,
                'message' => 'Falha ao executar killsource (HTTP ' . $res['http_code'] . '). Verifique usuário/senha admin.',
            ];
        }
        return ['success' => true, 'message' => 'Source interrompido via admin Icecast.'];
    }

    public function createAccount($account)
    {
        return $this->callProvisioningWebhook('create', $account);
    }

    public function suspendAccount($account)
    {
        $hook = $this->callProvisioningWebhook('suspend', $account);
        if ($hook['success']) {
            return $hook;
        }
        return $this->stopStream($account);
    }

    public function unsuspendAccount($account)
    {
        return $this->callProvisioningWebhook('unsuspend', $account);
    }

    public function terminateAccount($account)
    {
        return $this->callProvisioningWebhook('terminate', $account);
    }

    public function changePassword($account, $newPassword)
    {
        return $this->callProvisioningWebhook('change_password', $account, ['password' => $newPassword]);
    }

    public function changeBitrate($account, $bitrate)
    {
        return $this->callProvisioningWebhook('change_bitrate', $account, ['bitrate' => (int) $bitrate]);
    }

    public function changeMaxListeners($account, $max)
    {
        return $this->callProvisioningWebhook('change_listeners', $account, ['max_listeners' => (int) $max]);
    }

    private function callProvisioningWebhook($action, $account, array $extra = [])
    {
        if (empty($this->server->provisioning_url)) {
            return $this->unsupported($action);
        }
        $payload = json_encode(array_merge([
            'action' => $action,
            'account_id' => $account->id,
            'username' => $account->stream_username,
            'mount' => $account->mountpoint,
            'port' => $account->port,
        ], $extra));
        $headers = ['Content-Type: application/json'];
        $token = CentralVixStreamingModule::decryptSecret($this->server->api_password_enc);
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $res = $this->httpRequest($this->server->provisioning_url, 'POST', $payload, $headers);
        if (!$res['success']) {
            return [
                'success' => false,
                'message' => 'Webhook de provisionamento falhou (HTTP ' . $res['http_code'] . ').',
            ];
        }
        $json = json_decode($res['body'], true);
        if (is_array($json) && isset($json['success'])) {
            return $json;
        }
        return ['success' => true, 'message' => 'Provisionamento executado.'];
    }
}
