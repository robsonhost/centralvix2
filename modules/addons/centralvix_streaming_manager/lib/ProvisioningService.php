<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStreamingProvisioningService
{
    public static function onModuleCreate($serviceId)
    {
        $hosting = Capsule::table('tblhosting')->where('id', (int) $serviceId)->first();
        if (!$hosting) {
            return ['success' => false, 'message' => 'Serviço WHMCS não encontrado.'];
        }
        if (self::getAccountByService($serviceId)) {
            return ['success' => true, 'message' => 'Conta streaming já existe.'];
        }
        $plan = CentralVixStreamingService::planForProduct($hosting->packageid);
        $serverId = (int) ($plan['server_id'] ?? 0);
        if (!$serverId) {
            $default = Capsule::table('mod_centralvix_stream_servers')->where('active', 1)->orderBy('id')->first();
            $serverId = $default ? (int) $default->id : 0;
        }
        if (!$serverId) {
            CentralVixStreamingLogger::log('provision_create', 'failed', 0, 'Nenhum servidor streaming ativo');
            return ['success' => false, 'message' => 'Nenhum servidor streaming configurado.'];
        }
        $client = Capsule::table('tblclients')->where('id', $hosting->userid)->first();
        $username = 'cv' . $hosting->userid . 's' . $serviceId;
        $save = CentralVixStreamingService::saveAccount([
            'client_id' => $hosting->userid,
            'service_id' => $serviceId,
            'server_id' => $serverId,
            'stream_username' => $username,
            'port' => (int) ($plan['port'] ?? 8000),
            'mountpoint' => $plan['mountpoint'] ?? '/stream',
            'max_listeners' => (int) ($plan['max_listeners'] ?? 100),
            'bitrate' => (int) ($plan['bitrate'] ?? 128),
            'disk_space_mb' => (int) ($plan['disk_space_mb'] ?? 512),
            'autodj' => !empty($plan['autodj']),
            'status' => 'pending',
        ]);
        if (empty($save['success'])) {
            return $save;
        }
        $account = CentralVixStreamingService::getAccount($save['id']);
        $provider = CentralVixStreamingService::providerForAccount($account);
        $result = ['success' => true, 'message' => 'Conta registrada localmente.'];
        if ($provider) {
            $remote = $provider->createAccount($account);
            if (!$remote['success']) {
                CentralVixStreamingService::updateAccountStatus($account->id, 'pending');
                CentralVixStreamingLogger::log('provision_create', 'partial', $account->id, $remote['message']);
                return [
                    'success' => false,
                    'message' => $remote['message'] . ' Conta criada no WHMCS aguardando provisionamento manual.',
                ];
            }
            CentralVixStreamingService::updateAccountStatus($account->id, 'active');
            $result['message'] = $remote['message'];
        } else {
            CentralVixStreamingService::updateAccountStatus($account->id, 'pending');
            $result['message'] = 'Conta criada localmente. Provisionamento remoto não disponível.';
        }
        CentralVixStreamingLogger::log('provision_create', 'success', $account->id, $result['message']);
        CentralVixStreamingNotificationService::notifyClient($account->client_id, 'Conta de streaming criada', $result['message']);
        return $result;
    }

    public static function onModuleSuspend($serviceId)
    {
        return self::runAction($serviceId, 'suspend', 'suspended');
    }

    public static function onModuleUnsuspend($serviceId)
    {
        return self::runAction($serviceId, 'unsuspend', 'active');
    }

    public static function onModuleTerminate($serviceId)
    {
        return self::runAction($serviceId, 'terminate', 'cancelled');
    }

    public static function runAccountAction($accountId, $action, array $params = [])
    {
        $account = CentralVixStreamingService::getAccount($accountId);
        if (!$account) {
            return ['success' => false, 'message' => 'Conta não encontrada.'];
        }
        $provider = CentralVixStreamingService::providerForAccount($account);
        if (!$provider) {
            return ['success' => false, 'message' => 'Provider não disponível.'];
        }
        switch ($action) {
            case 'create':
                $result = $provider->createAccount($account);
                if ($result['success']) {
                    CentralVixStreamingService::updateAccountStatus($account->id, 'active');
                }
                break;
            case 'suspend':
                $result = $provider->suspendAccount($account);
                if ($result['success']) {
                    CentralVixStreamingService::updateAccountStatus($account->id, 'suspended');
                }
                break;
            case 'unsuspend':
                $result = $provider->unsuspendAccount($account);
                if ($result['success']) {
                    CentralVixStreamingService::updateAccountStatus($account->id, 'active');
                }
                break;
            case 'terminate':
                $result = $provider->terminateAccount($account);
                if ($result['success']) {
                    CentralVixStreamingService::updateAccountStatus($account->id, 'cancelled');
                }
                break;
            case 'change_password':
                $pass = $params['password'] ?? bin2hex(random_bytes(8));
                $result = $provider->changePassword($account, $pass);
                if ($result['success']) {
                    CentralVixStreamingService::saveAccount([
                        'stream_password' => $pass,
                        'client_id' => $account->client_id,
                        'service_id' => $account->service_id,
                        'server_id' => $account->server_id,
                        'stream_username' => $account->stream_username,
                        'port' => $account->port,
                        'mountpoint' => $account->mountpoint,
                        'max_listeners' => $account->max_listeners,
                        'bitrate' => $account->bitrate,
                        'status' => $account->status,
                    ], $account->id);
                    $result['password'] = '********';
                }
                break;
            case 'change_bitrate':
                $result = $provider->changeBitrate($account, (int) ($params['bitrate'] ?? $account->bitrate));
                if ($result['success']) {
                    Capsule::table('mod_centralvix_stream_accounts')->where('id', $account->id)->update([
                        'bitrate' => (int) $params['bitrate'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                break;
            case 'change_listeners':
                $result = $provider->changeMaxListeners($account, (int) ($params['max_listeners'] ?? $account->max_listeners));
                if ($result['success']) {
                    Capsule::table('mod_centralvix_stream_accounts')->where('id', $account->id)->update([
                        'max_listeners' => (int) $params['max_listeners'],
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                break;
            case 'start':
                $result = $provider->startStream($account);
                break;
            case 'stop':
                $result = $provider->stopStream($account);
                break;
            case 'restart':
                $result = $provider->restartStream($account);
                break;
            default:
                return ['success' => false, 'message' => 'Ação inválida.'];
        }
        CentralVixStreamingLogger::log($action, $result['success'] ? 'success' : 'failed', $account->id, $result['message'] ?? '');
        return $result;
    }

    private static function runAction($serviceId, $method, $status)
    {
        $account = CentralVixStreamingService::getAccountByService($serviceId);
        if (!$account) {
            return ['success' => true, 'message' => 'Sem conta streaming vinculada.'];
        }
        $map = [
            'suspend' => 'suspendAccount',
            'unsuspend' => 'unsuspendAccount',
            'terminate' => 'terminateAccount',
        ];
        $provider = CentralVixStreamingService::providerForAccount($account);
        if (!$provider) {
            CentralVixStreamingService::updateAccountStatus($account->id, $status);
            return ['success' => true, 'message' => 'Status local atualizado.'];
        }
        $result = $provider->{$map[$method]}($account);
        if ($result['success']) {
            CentralVixStreamingService::updateAccountStatus($account->id, $status);
        }
        CentralVixStreamingLogger::log('hook_' . $method, $result['success'] ? 'success' : 'failed', $account->id, $result['message'] ?? '');
        return $result;
    }

    private static function getAccountByService($serviceId)
    {
        return CentralVixStreamingService::getAccountByService($serviceId);
    }
}
