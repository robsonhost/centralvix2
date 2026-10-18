<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixAutoDJService
{
    public static function assertAccountOwner($accountId, $clientId, $isAdmin = false)
    {
        if ($isAdmin) {
            return true;
        }
        $account = Capsule::table('mod_centralvix_autodj_accounts')
            ->where('id', (int) $accountId)
            ->where('client_id', (int) $clientId)
            ->first();
        if (!$account) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['REQUEST_URI'] ?? '', 'ajax') !== false) {
                CentralVixAutoDJModule::json(['success' => false, 'message' => 'Acesso negado à conta.'], 403);
            }
            die('Acesso negado.');
        }
        return true;
    }

    public static function getAccount($id, $clientId = 0)
    {
        $q = Capsule::table('mod_centralvix_autodj_accounts')->where('id', (int) $id);
        if ($clientId && empty($_SESSION['adminid'])) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function getClientAccount($clientId)
    {
        return Capsule::table('mod_centralvix_autodj_accounts')
            ->where('client_id', (int) $clientId)
            ->where('status', 'active')
            ->orderBy('id')
            ->first();
    }

    public static function listAccounts()
    {
        return Capsule::table('mod_centralvix_autodj_accounts as a')
            ->leftJoin('tblclients as c', 'a.client_id', '=', 'c.id')
            ->select('a.*', Capsule::raw("CONCAT(c.firstname,' ',c.lastname) as client_name"))
            ->orderBy('a.id', 'desc')
            ->get();
    }

    public static function saveAccount(array $data, $id = 0)
    {
        $config = [];
        if (!empty($data['provider_config_json'])) {
            $decoded = json_decode($data['provider_config_json'], true);
            if (is_array($decoded)) {
                $config = $decoded;
            }
        } else {
            if ($id) {
                $existing = self::getAccount($id);
                $config = json_decode($existing->provider_config ?? '{}', true) ?: [];
            }
            if (!empty($data['provider_base_url'])) {
                $config['base_url'] = CentralVixAutoDJSecurity::sanitizeText($data['provider_base_url'], 300);
                $config['api_url'] = $config['base_url'];
            }
            if (!empty($data['provider_api_key'])) {
                $config['api_key'] = $data['provider_api_key'];
                $config['api_token'] = $data['provider_api_key'];
            }
            if (!empty($data['provider_station_id'])) {
                $config['station_id'] = $data['provider_station_id'];
            }
        }
        $row = [
            'client_id' => (int) ($data['client_id'] ?? 0),
            'service_id' => (int) ($data['service_id'] ?? 0),
            'name' => CentralVixAutoDJSecurity::sanitizeText($data['name'] ?? 'Minha Rádio', 150),
            'provider_type' => preg_replace('/[^a-z_]/', '', $data['provider_type'] ?? 'none'),
            'provider_config' => json_encode($config, JSON_UNESCAPED_UNICODE),
            'quota_mb' => max(512, (int) ($data['quota_mb'] ?? 5120)),
            'status' => preg_replace('/[^a-z_]/', '', $data['status'] ?? 'active'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            Capsule::table('mod_centralvix_autodj_accounts')->where('id', (int) $id)->update($row);
            return ['success' => true, 'id' => (int) $id];
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        $newId = (int) Capsule::table('mod_centralvix_autodj_accounts')->insertGetId($row);
        CentralVixAutoDJStorageService::accountDir($newId);
        return ['success' => true, 'id' => $newId];
    }

    public static function dashboardAdmin()
    {
        $accounts = Capsule::table('mod_centralvix_autodj_accounts')->count();
        $active = Capsule::table('mod_centralvix_autodj_accounts')->where('status', 'active')->count();
        $files = (int) Capsule::table('mod_centralvix_autodj_media')->count();
        $used = (int) Capsule::table('mod_centralvix_autodj_media')->sum('size_bytes');
        $problems = (int) Capsule::table('mod_centralvix_autodj_logs')
            ->where('result', 'failed')
            ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->count();
        return [
            'accounts' => $accounts,
            'active' => $active,
            'files' => $files,
            'used_gb' => round($used / 1073741824, 2),
            'problems' => $problems,
        ];
    }

    public static function dashboardClient($account)
    {
        if (!$account) {
            return null;
        }
        $quota = CentralVixAutoDJStorageService::quotaInfo($account);
        return [
            'media_count' => CentralVixAutoDJMediaLibrary::countByAccount($account->id),
            'playlists' => Capsule::table('mod_centralvix_autodj_playlists')->where('account_id', $account->id)->count(),
            'schedules' => Capsule::table('mod_centralvix_autodj_schedules')->where('account_id', $account->id)->where('active', 1)->count(),
            'quota' => $quota,
        ];
    }
}
