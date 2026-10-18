<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixStatusMonitorService
{
    public static function runAllChecks()
    {
        $services = Capsule::table('mod_centralvix_status_services')->where('enabled', 1)->get();
        $results = [];
        foreach ($services as $service) {
            $results[] = self::checkService($service);
        }
        return $results;
    }

    public static function checkService($service)
    {
        $type = $service->monitor_type;
        $result = ['service_id' => $service->id, 'status' => 'operational', 'response_time' => 0, 'message' => 'OK'];

        switch ($type) {
            case 'http':
            case 'https':
            case 'custom_url':
                $result = self::checkHttp($service);
                break;
            case 'tcp':
            case 'port':
                $result = self::checkTcp($service);
                break;
            case 'ping':
                $result = self::checkPing($service);
                break;
            case 'icecast':
            case 'shoutcast':
                $result = self::checkStreaming($service);
                break;
            case 'api_json':
                $result = self::checkApiJson($service);
                break;
            default:
                $result = self::checkHttp($service);
        }

        $prevStatus = $service->status;
        CentralVixStatusService::updateServiceStatus(
            $service->id,
            $result['status'],
            $result['response_time'],
            $result['message']
        );

        if ($prevStatus === 'operational' && $result['status'] !== 'operational') {
            CentralVixStatusNotificationService::notifyServiceDown($service, $result);
        } elseif ($prevStatus !== 'operational' && $result['status'] === 'operational') {
            CentralVixStatusNotificationService::notifyServiceRestored($service, $result);
        }

        return $result;
    }

    private static function checkHttp($service)
    {
        $url = $service->url;
        if ($url === '' && $service->host !== '') {
            $scheme = ($service->monitor_type === 'http') ? 'http' : 'https';
            $url = $scheme . '://' . $service->host;
            if ($service->port) {
                $url .= ':' . (int) $service->port;
            }
        }
        if (!CentralVixStatusSecurity::validateUrl($url)) {
            return self::fail('URL inválida ou bloqueada por segurança (SSRF).');
        }

        $timeout = (int) $service->timeout ?: 10;
        $expected = (int) $service->expected_http_code ?: 200;
        $start = microtime(true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'CentralVix-StatusMonitor/1.0',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        $ms = (int) round((microtime(true) - $start) * 1000);

        if ($err) {
            return self::fail($err, $ms, 'major_outage');
        }
        if ($code !== $expected) {
            return self::fail('HTTP ' . $code . ' (esperado ' . $expected . ')', $ms, 'partial_outage');
        }
        if ($ms > 3000) {
            return ['status' => 'degraded', 'response_time' => $ms, 'message' => 'Resposta lenta'];
        }
        return ['status' => 'operational', 'response_time' => $ms, 'message' => 'HTTP ' . $code];
    }

    private static function checkTcp($service)
    {
        $host = $service->host;
        $port = (int) $service->port;
        if (!CentralVixStatusSecurity::validateHost($host) || !CentralVixStatusSecurity::validatePort($port)) {
            return self::fail('Host/porta inválidos ou bloqueados.');
        }
        $timeout = (int) $service->timeout ?: 10;
        $start = microtime(true);
        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $ms = (int) round((microtime(true) - $start) * 1000);
        if (!$fp) {
            return self::fail($errstr ?: 'Conexão TCP falhou', $ms, 'major_outage');
        }
        fclose($fp);
        return ['status' => 'operational', 'response_time' => $ms, 'message' => 'Porta aberta'];
    }

    private static function checkPing($service)
    {
        $host = $service->host;
        if (!CentralVixStatusSecurity::validateHost($host)) {
            return self::fail('Host bloqueado por política de segurança.');
        }
        if (!function_exists('exec')) {
            return self::fail('PING não disponível (exec desabilitado). Use TCP/HTTP.');
        }
        $timeout = (int) $service->timeout ?: 5;
        $cmd = (stripos(PHP_OS, 'WIN') === 0)
            ? 'ping -n 1 -w ' . ($timeout * 1000) . ' ' . escapeshellarg($host)
            : 'ping -c 1 -W ' . (int) $timeout . ' ' . escapeshellarg($host);
        $start = microtime(true);
        exec($cmd, $out, $code);
        $ms = (int) round((microtime(true) - $start) * 1000);
        if ($code !== 0) {
            return self::fail('Host unreachable', $ms, 'major_outage');
        }
        return ['status' => 'operational', 'response_time' => $ms, 'message' => 'PING OK'];
    }

    private static function checkStreaming($service)
    {
        $url = $service->url;
        if ($url === '' && $service->host) {
            $url = 'http://' . $service->host . ':' . ((int) $service->port ?: 8000);
            if ($service->monitor_type === 'icecast' && strpos($url, '/status-json.xsl') === false) {
                $url .= '/status-json.xsl';
            }
        }
        if (!CentralVixStatusSecurity::validateUrl($url)) {
            return self::fail('URL de streaming inválida.');
        }
        $r = self::checkHttp((object) array_merge((array) $service, ['url' => $url, 'expected_http_code' => 200]));
        if ($r['status'] !== 'operational') {
            $r['status'] = 'major_outage';
            $r['message'] = 'Stream offline';
        }
        return $r;
    }

    private static function checkApiJson($service)
    {
        $url = $service->url;
        if ($url === '' && $service->host !== '') {
            $url = 'https://' . $service->host;
        }
        if (!CentralVixStatusSecurity::validateUrl($url)) {
            return self::fail('URL API inválida ou bloqueada.');
        }
        $r = self::checkHttp((object) array_merge((array) $service, ['url' => $url]));
        if ($r['status'] !== 'operational') {
            return $r;
        }
        $timeout = (int) $service->timeout ?: 10;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'CentralVix-StatusMonitor/1.0',
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false || $body === '') {
            return self::fail('Resposta API inválida', $r['response_time'], 'partial_outage');
        }
        json_decode($body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return self::fail('JSON inválido', $r['response_time'], 'partial_outage');
        }
        return $r;
    }

    private static function fail($msg, $ms = 0, $status = 'major_outage')
    {
        return ['status' => $status, 'response_time' => (int) $ms, 'message' => $msg];
    }
}
