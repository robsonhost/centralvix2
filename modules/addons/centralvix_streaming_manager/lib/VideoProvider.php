<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Provider placeholder para streaming de vídeo.
 * Integrações futuras (Wowza, Flussonic, etc.) devem estender esta classe.
 */
class CentralVixVideoProvider extends CentralVixStreamingProviderBase
{
    public function getType()
    {
        return 'video';
    }

    public function testConnection()
    {
        if (empty($this->server->provisioning_url)) {
            return [
                'success' => false,
                'message' => 'Provedor de vídeo ainda não configurado. Informe URL de API/painel.',
            ];
        }
        $res = $this->httpRequest($this->server->provisioning_url . '/health', 'GET');
        if ($res['success']) {
            return ['success' => true, 'message' => 'Endpoint de vídeo respondeu.'];
        }
        return [
            'success' => false,
            'message' => 'Provedor de vídeo indisponível. Configure adapter específico em VideoProvider.php.',
        ];
    }

    public function getLiveStats($account)
    {
        if (empty($this->server->provisioning_url)) {
            return [
                'online' => false,
                'listeners' => 0,
                'peak_listeners' => 0,
                'bitrate' => (int) $account->bitrate,
                'message' => 'Stats de vídeo requerem API configurada',
            ];
        }
        $url = rtrim($this->server->provisioning_url, '/') . '/stats/' . (int) $account->id;
        $headers = ['Content-Type: application/json'];
        $token = CentralVixStreamingModule::decryptSecret($this->server->api_password_enc);
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $res = $this->httpRequest($url, 'GET', null, $headers);
        if (!$res['success']) {
            return [
                'online' => false,
                'listeners' => 0,
                'peak_listeners' => 0,
                'bitrate' => (int) $account->bitrate,
            ];
        }
        $json = json_decode($res['body'], true);
        if (!is_array($json)) {
            return ['online' => false, 'listeners' => 0, 'peak_listeners' => 0, 'bitrate' => (int) $account->bitrate];
        }
        return [
            'online' => !empty($json['online']),
            'listeners' => (int) ($json['viewers'] ?? $json['listeners'] ?? 0),
            'peak_listeners' => (int) ($json['peak'] ?? 0),
            'bitrate' => (int) ($json['bitrate'] ?? $account->bitrate),
            'bytes' => (int) ($json['bytes'] ?? 0),
        ];
    }

    public function getCapabilities()
    {
        $caps = parent::getCapabilities();
        if (!empty($this->server->provisioning_url)) {
            foreach (array_keys($caps) as $k) {
                $caps[$k] = true;
            }
        }
        return $caps;
    }

    public function createAccount($account)
    {
        return $this->apiAction('create', $account);
    }

    public function suspendAccount($account)
    {
        return $this->apiAction('suspend', $account);
    }

    public function unsuspendAccount($account)
    {
        return $this->apiAction('unsuspend', $account);
    }

    public function terminateAccount($account)
    {
        return $this->apiAction('terminate', $account);
    }

    public function changePassword($account, $newPassword)
    {
        return $this->apiAction('change_password', $account, ['password' => $newPassword]);
    }

    private function apiAction($action, $account, array $extra = [])
    {
        if (empty($this->server->provisioning_url)) {
            return [
                'success' => false,
                'message' => 'Integração de vídeo não implementada para este servidor. Configure provisioning_url.',
            ];
        }
        $payload = json_encode(array_merge(['action' => $action, 'account_id' => $account->id], $extra));
        $headers = ['Content-Type: application/json'];
        $token = CentralVixStreamingModule::decryptSecret($this->server->api_password_enc);
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $res = $this->httpRequest($this->server->provisioning_url, 'POST', $payload, $headers);
        return [
            'success' => $res['success'],
            'message' => $res['success'] ? 'OK' : 'Falha HTTP ' . $res['http_code'],
        ];
    }
}
