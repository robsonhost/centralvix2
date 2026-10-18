<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixUpdPackageService
{
    const MAX_BYTES = 524288000;

    public static function handleUpload($versionId, array $file)
    {
        $version = CentralVixUpdVersionService::get($versionId);
        if (!$version) {
            return ['success' => false, 'message' => 'Versão não encontrada.'];
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado.'];
        }

        if ($file['size'] > self::MAX_BYTES) {
            return ['success' => false, 'message' => 'Arquivo excede limite de 500 MB.'];
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return ['success' => false, 'message' => 'Apenas arquivos ZIP são permitidos.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/octet-stream'];
        if (!in_array($mime, $allowed, true)) {
            return ['success' => false, 'message' => 'Tipo MIME inválido: ' . $mime];
        }

        $slip = CentralVixUpdSecurity::scanZipForSlip($file['tmp_name']);
        if (!$slip['ok']) {
            return ['success' => false, 'message' => $slip['message']];
        }

        $product = CentralVixUpdProductService::get($version->product_id);
        $dir = CentralVixUpdModule::storagePath('packages/' . $product->slug . '/' . $version->version);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $filename = CentralVixUpdSecurity::safeBasename($product->slug . '-' . $version->version . '.zip');
        $dest = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Falha ao salvar pacote.'];
        }

        @chmod($dest, 0640);
        $sha256 = hash_file('sha256', $dest);
        $signature = CentralVixUpdSignatureService::signHash($sha256);
        $relative = 'packages/' . $product->slug . '/' . $version->version . '/' . $filename;
        $size = filesize($dest);

        CentralVixUpdVersionService::attachPackage($versionId, $relative, $size, $sha256, $signature);
        CentralVixUpdLogger::log(
            'package_upload',
            'Pacote enviado v' . $version->version,
            ['sha256' => $sha256, 'size' => $size],
            (int) $version->product_id,
            (int) $versionId
        );

        return [
            'success' => true,
            'sha256' => $sha256,
            'signature' => $signature,
            'size' => $size,
        ];
    }
}
