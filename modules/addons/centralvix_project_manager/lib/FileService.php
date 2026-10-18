<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixPmFileService
{
    const ALLOWED = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'text/plain', 'application/zip',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public static function ensureStorage()
    {
        $root = CentralVixPmModule::storagePath();
        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
        }
        $ht = $root . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\nDeny from all\n</FilesMatch>\n");
        }
    }

    public static function projectDir($projectId)
    {
        return CentralVixPmModule::storagePath() . '/p' . (int) $projectId;
    }

    public static function listByProject($projectId, $taskId = null, $clientView = false)
    {
        $q = Capsule::table('mod_centralvix_pm_files')->where('project_id', (int) $projectId);
        if ($taskId) {
            $q->where('task_id', (int) $taskId);
        }
        if ($clientView) {
            $q->where('visibility', 'client');
        }
        return $q->orderBy('id', 'desc')->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_pm_files as f')
            ->join('mod_centralvix_pm_projects as p', 'p.id', '=', 'f.project_id')
            ->where('f.id', (int) $id)
            ->select('f.*', 'p.client_id', 'p.client_visible');
        if ($clientId !== null) {
            $q->where('p.client_id', (int) $clientId)->where('p.client_visible', 1)->where('f.visibility', 'client');
        }
        return $q->first();
    }

    public static function upload($projectId, $taskId, $uploaderType, $uploaderId, $visibility = 'internal', $clientId = null)
    {
        $project = CentralVixPmProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        if ($clientId !== null) {
            $visibility = 'client';
        }
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload.'];
        }
        $file = $_FILES['file'];
        $maxMb = (int) CentralVixPmModule::setting('max_upload_mb', 25);
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['success' => false, 'message' => 'Arquivo excede ' . $maxMb . ' MB.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED, true)) {
            return ['success' => false, 'message' => 'Tipo não permitido.'];
        }
        self::ensureStorage();
        $dir = self::projectDir($projectId);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $stored = 'f_' . bin2hex(random_bytes(12)) . ($ext ? '.' . preg_replace('/[^a-z0-9]/i', '', $ext) : '');
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Falha ao salvar.'];
        }
        $id = Capsule::table('mod_centralvix_pm_files')->insertGetId([
            'project_id' => (int) $projectId,
            'task_id' => $taskId ? (int) $taskId : null,
            'uploader_type' => $uploaderType,
            'uploader_id' => (int) $uploaderId,
            'stored_name' => $stored,
            'original_name' => CentralVixPmSecurity::safeFilename($file['name']),
            'mime_type' => $mime,
            'size_bytes' => (int) $file['size'],
            'visibility' => $visibility === 'client' ? 'client' : 'internal',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixPmLogger::log($projectId, 'file_upload', 'Anexo: ' . $file['name'], $taskId, $uploaderType, $uploaderId);
        return ['success' => true, 'id' => $id];
    }

    public static function resolvePath($record)
    {
        if (!$record) {
            return null;
        }
        $path = self::projectDir($record->project_id) . '/' . basename($record->stored_name);
        $base = realpath(self::projectDir($record->project_id));
        $real = realpath($path);
        if (!$base || !$real || strpos($real, $base) !== 0) {
            return null;
        }
        return $real;
    }

    public static function serveDownload($fileId, $clientId = null)
    {
        $file = self::get($fileId, $clientId);
        if (!$file) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
        $path = self::resolvePath($file);
        if (!$path || !is_file($path)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
        header('Content-Type: ' . ($file->mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . str_replace('"', '', $file->original_name) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
}
