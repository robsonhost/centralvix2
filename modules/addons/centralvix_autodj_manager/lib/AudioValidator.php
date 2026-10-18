<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixAutoDJAudioValidator
{
    public static function allowedExtensions()
    {
        $ext = ['mp3'];
        if (CentralVixAutoDJModule::setting('allow_aac', '') === 'on') {
            $ext[] = 'aac';
            $ext[] = 'm4a';
        }
        return $ext;
    }

    public static function allowedMimes()
    {
        $m = ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg', 'application/octet-stream'];
        if (CentralVixAutoDJModule::setting('allow_aac', '') === 'on') {
            $m[] = 'audio/aac';
            $m[] = 'audio/x-aac';
            $m[] = 'audio/mp4';
        }
        return $m;
    }

    public static function maxBytes()
    {
        $mb = max(1, (int) CentralVixAutoDJModule::setting('max_upload_mb', 50));
        return $mb * 1048576;
    }

    public static function validateUpload(array $file)
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'message' => 'Upload inválido.'];
        }
        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'message' => 'Erro no upload (código ' . (int) $file['error'] . ').'];
        }
        if ($file['size'] > self::maxBytes()) {
            return ['valid' => false, 'message' => 'Arquivo excede tamanho máximo permitido.'];
        }
        $name = CentralVixAutoDJSecurity::safeFilename($file['name'] ?? 'audio.mp3');
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $blocked = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'exe', 'sh', 'bat', 'cmd', 'com', 'js', 'html', 'htm', 'svg'];
        if (in_array($ext, $blocked, true)) {
            return ['valid' => false, 'message' => 'Tipo de arquivo bloqueado por segurança.'];
        }
        if (!in_array($ext, self::allowedExtensions(), true)) {
            return ['valid' => false, 'message' => 'Extensão não permitida. Use: ' . implode(', ', self::allowedExtensions())];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : ($file['type'] ?? '');
        if ($finfo) {
            finfo_close($finfo);
        }
        if ($mime && !in_array($mime, self::allowedMimes(), true) && strpos($mime, 'audio/') !== 0) {
            return ['valid' => false, 'message' => 'MIME não permitido: ' . $mime];
        }
        $duration = self::estimateDuration($file['tmp_name'], $ext);
        return [
            'valid' => true,
            'original_name' => $name,
            'extension' => $ext,
            'mime' => $mime ?: 'audio/mpeg',
            'size' => (int) $file['size'],
            'duration' => $duration,
        ];
    }

    public static function estimateDuration($path, $ext)
    {
        if ($ext === 'mp3') {
            $h = @fopen($path, 'rb');
            if (!$h) {
                return 0;
            }
            fseek($h, -128, SEEK_END);
            $tag = fread($h, 128);
            fclose($h);
            if (preg_match('/(\d+)\s*$/', $tag, $m)) {
                return (int) $m[1];
            }
        }
        $size = filesize($path);
        if ($size > 0) {
            return (int) round(($size * 8) / (128 * 1000));
        }
        return 0;
    }
}
