<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixMigFileService
{
    const ALLOWED_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'text/plain',
        'application/zip', 'application/x-zip-compressed',
    ];

    public static function ensureStorage()
    {
        CentralVixMigModule::ensureStorageDirs();
    }

    public static function maxUploadBytes()
    {
        $mb = (int) CentralVixMigModule::setting('max_upload_mb', '25');
        return max(1, $mb) * 1024 * 1024;
    }

    public static function handleUpload($requestId, array $file, $clientId, $fileType = 'doc')
    {
        self::ensureStorage();
        $request = CentralVixMigMigrationService::get($requestId);
        if (!$request) {
            return ['success' => false, 'message' => 'Solicitação não encontrada.'];
        }
        if ((int) $request->client_id !== (int) $clientId && !CentralVixMigSecurity::isAdminSession()) {
            return ['success' => false, 'message' => 'Acesso negado.'];
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['success' => false, 'message' => 'Arquivo inválido.'];
        }
        if ($file['size'] > self::maxUploadBytes()) {
            return ['success' => false, 'message' => 'Arquivo excede limite permitido.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido.'];
        }

        $allowedBackup = CentralVixMigModule::dbSetting('allow_client_backup_upload', '0') === '1';
        $fileType = in_array($fileType, ['screenshot', 'doc', 'backup'], true) ? $fileType : 'doc';
        if ($fileType === 'backup' && !$allowedBackup) {
            return ['success' => false, 'message' => 'Upload de backup não habilitado.'];
        }

        $dir = CentralVixMigModule::storagePath('uploads/' . (int) $requestId);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        $original = preg_replace('/[^a-zA-Z0-9._\- ]/', '_', basename($file['name']));
        $stored = bin2hex(random_bytes(16)) . '_' . $original;
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Falha ao salvar arquivo.'];
        }
        @chmod($dest, 0640);

        $uploaderType = CentralVixMigSecurity::isAdminSession() ? 'admin' : 'client';
        $uploaderId = CentralVixMigSecurity::isAdminSession() ? CentralVixMigSecurity::adminId() : (int) $clientId;

        $id = Capsule::table('mod_centralvix_migration_files')->insertGetId([
            'request_id' => (int) $requestId,
            'uploader_type' => $uploaderType,
            'uploader_id' => $uploaderId,
            'stored_name' => $stored,
            'original_name' => mb_substr($original, 0, 255),
            'mime_type' => $mime,
            'size_bytes' => (int) $file['size'],
            'file_type' => $fileType,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixMigLogger::log($requestId, 'file_upload', 'Arquivo anexado: ' . $original, ['file_id' => $id], $uploaderType, $uploaderId);

        return ['success' => true, 'file_id' => $id];
    }

    public static function listForRequest($requestId)
    {
        return Capsule::table('mod_centralvix_migration_files')
            ->where('request_id', (int) $requestId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function get($fileId)
    {
        return Capsule::table('mod_centralvix_migration_files')->where('id', (int) $fileId)->first();
    }

    public static function serveDownload($fileId, $clientId = null)
    {
        $file = self::get($fileId);
        if (!$file) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
        $request = CentralVixMigMigrationService::get($file->request_id);
        if (!$request) {
            http_response_code(404);
            exit;
        }
        if ($clientId !== null && (int) $request->client_id !== (int) $clientId) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $path = CentralVixMigModule::storagePath('uploads/' . $file->request_id . '/' . $file->stored_name);
        $base = CentralVixMigModule::storagePath('uploads');
        if (!CentralVixMigSecurity::isPathInside($path, $base) || !is_file($path)) {
            http_response_code(404);
            exit;
        }

        CentralVixMigLogger::log($file->request_id, 'file_download', 'Download: ' . $file->original_name, ['file_id' => $fileId]);

        header('Content-Type: ' . ($file->mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file->original_name) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
}
