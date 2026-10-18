<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStreamingService
{
    public static function listServers($activeOnly = false)
    {
        $q = Capsule::table('mod_centralvix_stream_servers')->orderBy('name');
        if ($activeOnly) {
            $q->where('active', 1);
        }
        return $q->get();
    }

    public static function getServer($id)
    {
        return Capsule::table('mod_centralvix_stream_servers')->where('id', (int) $id)->first();
    }

    public static function saveServer(array $data, $id = 0)
    {
        $hostname = CentralVixStreamingSecurity::sanitizeText($data['hostname'] ?? '', 255);
        if (!CentralVixStreamingSecurity::validateHostname($hostname)) {
            return ['success' => false, 'message' => 'Hostname/IP inválido.'];
        }
        $apiPort = (int) ($data['api_port'] ?? 8000);
        if (!CentralVixStreamingSecurity::validatePort($apiPort)) {
            return ['success' => false, 'message' => 'Porta inválida.'];
        }
        $row = [
            'name' => CentralVixStreamingSecurity::sanitizeText($data['name'] ?? '', 150),
            'type' => preg_replace('/[^a-z_]/', '', $data['type'] ?? 'icecast'),
            'hostname' => $hostname,
            'api_port' => $apiPort,
            'stream_port' => (int) ($data['stream_port'] ?? $apiPort),
            'use_ssl' => !empty($data['use_ssl']) ? 1 : 0,
            'api_user' => CentralVixStreamingSecurity::sanitizeText($data['api_user'] ?? 'admin', 100),
            'capacity' => (int) ($data['capacity'] ?? 100),
            'active' => !empty($data['active']) ? 1 : 0,
            'location' => CentralVixStreamingSecurity::sanitizeText($data['location'] ?? '', 100),
            'provisioning_url' => CentralVixStreamingSecurity::sanitizeText($data['provisioning_url'] ?? '', 500),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['api_password']) && $data['api_password'] !== '********') {
            $row['api_password_enc'] = CentralVixStreamingModule::encryptSecret($data['api_password']);
        }
        if ($id) {
            if (empty($row['api_password_enc'])) {
                unset($row['api_password_enc']);
            }
            Capsule::table('mod_centralvix_stream_servers')->where('id', (int) $id)->update($row);
            return ['success' => true, 'id' => (int) $id];
        }
        if (empty($row['api_password_enc'])) {
            $row['api_password_enc'] = '';
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = (int) Capsule::table('mod_centralvix_stream_servers')->insertGetId($row);
        return ['success' => true, 'id' => $newId];
    }

    public static function listAccounts($clientId = 0, $status = null)
    {
        $q = Capsule::table('mod_centralvix_stream_accounts as a')
            ->leftJoin('mod_centralvix_stream_servers as s', 'a.server_id', '=', 's.id')
            ->leftJoin('tblclients as c', 'a.client_id', '=', 'c.id')
            ->select('a.*', 's.name as server_name', 's.type as server_type', 's.hostname', 's.use_ssl', 's.api_port as server_port')
            ->orderBy('a.id', 'desc');
        if ($clientId) {
            $q->where('a.client_id', (int) $clientId);
        }
        if ($status) {
            $q->where('a.status', $status);
        }
        return $q->get();
    }

    public static function getAccount($id, $clientId = 0)
    {
        $q = Capsule::table('mod_centralvix_stream_accounts as a')
            ->leftJoin('mod_centralvix_stream_servers as s', 'a.server_id', '=', 's.id')
            ->select('a.*', 's.name as server_name', 's.type as server_type', 's.hostname', 's.use_ssl', 's.api_port', 's.stream_port', 's.provisioning_url')
            ->where('a.id', (int) $id);
        if ($clientId) {
            $q->where('a.client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function getAccountByService($serviceId)
    {
        return Capsule::table('mod_centralvix_stream_accounts')->where('service_id', (int) $serviceId)->first();
    }

    public static function saveAccount(array $data, $id = 0)
    {
        $row = [
            'client_id' => (int) ($data['client_id'] ?? 0),
            'service_id' => (int) ($data['service_id'] ?? 0),
            'server_id' => (int) ($data['server_id'] ?? 0),
            'stream_username' => CentralVixStreamingSecurity::sanitizeText($data['stream_username'] ?? '', 100),
            'port' => (int) ($data['port'] ?? 8000),
            'mountpoint' => CentralVixStreamingSecurity::sanitizeText($data['mountpoint'] ?? '/stream', 100),
            'max_listeners' => (int) ($data['max_listeners'] ?? 100),
            'bitrate' => (int) ($data['bitrate'] ?? 128),
            'disk_space_mb' => (int) ($data['disk_space_mb'] ?? 0),
            'autodj' => !empty($data['autodj']) ? 1 : 0,
            'status' => preg_replace('/[^a-z_]/', '', $data['status'] ?? 'pending'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($data['stream_password']) && $data['stream_password'] !== '********') {
            $row['stream_password_enc'] = CentralVixStreamingModule::encryptSecret($data['stream_password']);
        }
        if ($id) {
            if (empty($row['stream_password_enc'])) {
                unset($row['stream_password_enc']);
            }
            Capsule::table('mod_centralvix_stream_accounts')->where('id', (int) $id)->update($row);
            return ['success' => true, 'id' => (int) $id];
        }
        if (empty($row['stream_password_enc'])) {
            $row['stream_password_enc'] = CentralVixStreamingModule::encryptSecret(bin2hex(random_bytes(8)));
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = (int) Capsule::table('mod_centralvix_stream_accounts')->insertGetId($row);
        return ['success' => true, 'id' => $newId];
    }

    public static function updateAccountStatus($id, $status)
    {
        Capsule::table('mod_centralvix_stream_accounts')->where('id', (int) $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function streamUrl($account, $server = null)
    {
        $server = $server ?: self::getServer($account->server_id);
        if (!$server) {
            return '';
        }
        $scheme = !empty($server->use_ssl) ? 'https' : 'http';
        $port = (int) ($account->port ?: $server->stream_port ?: $server->api_port);
        $mount = $account->mountpoint ?: '/stream';
        if ($mount[0] !== '/') {
            $mount = '/' . $mount;
        }
        if ($server->type === 'shoutcast') {
            return $scheme . '://' . $server->hostname . ':' . $port . '/';
        }
        return $scheme . '://' . $server->hostname . ':' . $port . $mount;
    }

    public static function dashboardStats()
    {
        $accounts = Capsule::table('mod_centralvix_stream_accounts')->count();
        $active = Capsule::table('mod_centralvix_stream_accounts')->where('status', 'active')->count();
        $suspended = Capsule::table('mod_centralvix_stream_accounts')->where('status', 'suspended')->count();
        $servers = Capsule::table('mod_centralvix_stream_servers')->where('active', 1)->count();
        $listeners = (int) Capsule::table('mod_centralvix_stream_stats')
            ->where('recorded_at', '>=', date('Y-m-d H:i:s', strtotime('-1 hour')))
            ->max('listeners');
        return [
            'accounts' => (int) $accounts,
            'active' => (int) $active,
            'suspended' => (int) $suspended,
            'servers' => (int) $servers,
            'listeners' => $listeners,
        ];
    }

    public static function getPlans()
    {
        $json = CentralVixStreamingModule::dbSetting('plans_json', '[]');
        $plans = json_decode($json, true);
        return is_array($plans) ? $plans : [];
    }

    public static function savePlans(array $plans)
    {
        CentralVixStreamingModule::setDbSetting('plans_json', json_encode(array_values($plans), JSON_UNESCAPED_UNICODE));
    }

    public static function planForProduct($productId)
    {
        foreach (self::getPlans() as $plan) {
            if ((int) ($plan['product_id'] ?? 0) === (int) $productId) {
                return $plan;
            }
        }
        return null;
    }

    public static function listProducts()
    {
        return Capsule::table('tblproducts')->orderBy('name')->get(['id', 'name']);
    }

    public static function providerForAccount($account)
    {
        $server = self::getServer($account->server_id);
        return CentralVixStreamingServerFactory::provider($server);
    }
}
