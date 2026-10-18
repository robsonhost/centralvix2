<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

abstract class CentralVixStreamMetadataAdapter
{
    protected $timeout = 10;

    abstract public function getType();

    abstract public function fetch($streamUrl);

    protected function httpGet($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 2,
            CURLOPT_USERAGENT => 'CentralVix-PlayerGenerator/1.0',
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code < 200 || $code >= 400 || $body === false) {
            return null;
        }
        return $body;
    }

    protected function emptyMeta()
    {
        return [
            'title' => '',
            'artist' => '',
            'listeners' => 0,
            'cover' => '',
            'online' => false,
        ];
    }
}

class CentralVixIcecastMetadataAdapter extends CentralVixStreamMetadataAdapter
{
    public function getType()
    {
        return 'icecast';
    }

    public function fetch($streamUrl)
    {
        $parts = parse_url($streamUrl);
        if (!$parts || empty($parts['host'])) {
            return $this->emptyMeta();
        }
        $scheme = ($parts['scheme'] ?? 'http') . '://';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $statusUrl = $scheme . $parts['host'] . $port . '/status-json.xsl';
        $body = $this->httpGet($statusUrl);
        if (!$body) {
            return $this->emptyMeta();
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            return $this->emptyMeta();
        }
        $sources = $json['icestats']['source'] ?? [];
        if (isset($sources['title'])) {
            $sources = [$sources];
        }
        $path = $parts['path'] ?? '/';
        $src = null;
        foreach ((array) $sources as $s) {
            if (!empty($s['listenurl']) && strpos($s['listenurl'], $path) !== false) {
                $src = $s;
                break;
            }
        }
        if (!$src && count((array) $sources) === 1) {
            $src = is_array($sources) ? reset($sources) : null;
        }
        if (!$src) {
            return $this->emptyMeta();
        }
        $title = $src['title'] ?? '';
        $artist = '';
        if (strpos($title, ' - ') !== false) {
            list($artist, $title) = explode(' - ', $title, 2);
        }
        return [
            'title' => trim($title),
            'artist' => trim($artist),
            'listeners' => (int) ($src['listeners'] ?? 0),
            'cover' => '',
            'online' => true,
        ];
    }
}

class CentralVixShoutcastMetadataAdapter extends CentralVixStreamMetadataAdapter
{
    public function getType()
    {
        return 'shoutcast';
    }

    public function fetch($streamUrl)
    {
        $parts = parse_url($streamUrl);
        if (!$parts || empty($parts['host'])) {
            return $this->emptyMeta();
        }
        $scheme = ($parts['scheme'] ?? 'http') . '://';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $url = $scheme . $parts['host'] . $port . '/7.html';
        $body = $this->httpGet($url);
        if (!$body) {
            return $this->emptyMeta();
        }
        $lines = explode(',', strip_tags($body));
        if (count($lines) < 7) {
            return $this->emptyMeta();
        }
        $song = trim($lines[6] ?? '');
        $artist = '';
        $title = $song;
        if (strpos($song, ' - ') !== false) {
            list($artist, $title) = explode(' - ', $song, 2);
        }
        return [
            'title' => trim($title),
            'artist' => trim($artist),
            'listeners' => (int) trim($lines[0] ?? 0),
            'cover' => '',
            'online' => (int) trim($lines[1] ?? 0) > 0,
        ];
    }
}

class CentralVixAzuraCastMetadataAdapter extends CentralVixStreamMetadataAdapter
{
    public function getType()
    {
        return 'azuracast';
    }

    public function fetch($streamUrl)
    {
        return $this->emptyMeta();
    }
}

class CentralVixStreamMetadata
{
    public static function detectAdapter($streamUrl)
    {
        $url = strtolower($streamUrl);
        if (strpos($url, '.m3u8') !== false || strpos($url, '/hls') !== false) {
            return null;
        }
        return new CentralVixIcecastMetadataAdapter();
    }

    public static function fetch($streamUrl, $adapterType = 'auto')
    {
        try {
            if ($adapterType === 'shoutcast') {
                $adapter = new CentralVixShoutcastMetadataAdapter();
            } elseif ($adapterType === 'azuracast') {
                $adapter = new CentralVixAzuraCastMetadataAdapter();
            } elseif ($adapterType === 'icecast') {
                $adapter = new CentralVixIcecastMetadataAdapter();
            } else {
                $ice = new CentralVixIcecastMetadataAdapter();
                $meta = $ice->fetch($streamUrl);
                if (!empty($meta['online']) || !empty($meta['title'])) {
                    return array_merge([
                        'title' => '', 'artist' => '', 'listeners' => 0, 'cover' => '', 'online' => false,
                    ], $meta);
                }
                $adapter = new CentralVixShoutcastMetadataAdapter();
            }
            $meta = $adapter->fetch($streamUrl);
            return array_merge([
                'title' => '',
                'artist' => '',
                'listeners' => 0,
                'cover' => '',
                'online' => false,
            ], $meta ?: []);
        } catch (Throwable $e) {
            return [
                'title' => '',
                'artist' => '',
                'listeners' => 0,
                'cover' => '',
                'online' => false,
            ];
        }
    }
}
