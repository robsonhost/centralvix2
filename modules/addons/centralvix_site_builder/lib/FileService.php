<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSiteFileService
{
    const CATEGORIES = [
        'logo' => 'Logo',
        'photos' => 'Fotos',
        'texts' => 'Textos',
        'pdf' => 'PDF',
        'documents' => 'Documentos',
    ];

    const ALLOWED_MIMES = [
        'logo' => ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml', 'image/gif'],
        'photos' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'texts' => ['text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'pdf' => ['application/pdf'],
        'documents' => [
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'image/jpeg', 'image/png',
        ],
    ];

    public static function ensureStorage()
    {
        $root = CentralVixSiteModule::storagePath();
        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
        }
        $ht = $root . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php5|php7|phar)$\">\nDeny from all\n</FilesMatch>\n");
        }
    }

    public static function listByProject($projectId)
    {
        return Capsule::table('mod_centralvix_site_files')
            ->where('project_id', (int) $projectId)
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_site_files')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function upload($projectId, $clientId, $uploaderType, $uploaderId, $category, $fileField = 'file')
    {
        $project = CentralVixSiteProjectService::get($projectId, $uploaderType === 'client' ? $clientId : null);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        if (!isset(self::CATEGORIES[$category])) {
            return ['success' => false, 'message' => 'Categoria inválida.'];
        }
        if (empty($_FILES[$fileField]) || $_FILES[$fileField]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'message' => 'Nenhum arquivo enviado.'];
        }
        $file = $_FILES[$fileField];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload.'];
        }
        $maxMb = (int) CentralVixSiteModule::setting('max_upload_mb', 25);
        if ($maxMb < 1) {
            $maxMb = 25;
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['success' => false, 'message' => 'Arquivo excede ' . $maxMb . ' MB.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed = self::ALLOWED_MIMES[$category];
        if (!in_array($mime, $allowed, true)) {
            return ['success' => false, 'message' => 'Tipo de arquivo não permitido para esta categoria.'];
        }
        self::ensureStorage();
        $dir = self::projectDir($projectId);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $ext = preg_replace('/[^a-zA-Z0-9]/', '', $ext);
        $stored = 'f_' . bin2hex(random_bytes(12)) . ($ext ? '.' . $ext : '');
        $dest = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return ['success' => false, 'message' => 'Falha ao salvar arquivo.'];
        }
        $id = Capsule::table('mod_centralvix_site_files')->insertGetId([
            'project_id' => (int) $projectId,
            'client_id' => (int) $project->client_id,
            'uploader_type' => $uploaderType,
            'uploader_id' => (int) $uploaderId,
            'file_category' => $category,
            'stored_name' => $stored,
            'original_name' => CentralVixSiteSecurity::safeFilename($file['name']),
            'mime_type' => $mime,
            'size_bytes' => (int) $file['size'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixSiteTimelineService::add(
            $projectId,
            'file_uploaded',
            'Arquivo enviado',
            self::CATEGORIES[$category] . ': ' . $file['name'],
            $uploaderType,
            $uploaderId,
            $uploaderType === 'client' ? CentralVixSiteModule::clientName($clientId) : CentralVixSiteModule::adminName($uploaderId)
        );
        if ($project->status === 'aguardando_materiais' || $project->status === 'briefing_recebido') {
            CentralVixSiteProjectService::changeStatus($projectId, 'aguardando_materiais', $uploaderType, $uploaderId, $uploaderType === 'client' ? CentralVixSiteModule::clientName($clientId) : CentralVixSiteModule::adminName($uploaderId), 'Material recebido');
        }
        return ['success' => true, 'id' => $id, 'name' => $file['name']];
    }

    public static function projectDir($projectId)
    {
        return CentralVixSiteModule::storagePath() . '/p' . (int) $projectId;
    }

    public static function resolvePath($fileRecord)
    {
        if (!$fileRecord) {
            return null;
        }
        $path = self::projectDir($fileRecord->project_id) . '/' . basename($fileRecord->stored_name);
        $realBase = realpath(self::projectDir($fileRecord->project_id));
        $realPath = realpath($path);
        if (!$realBase || !$realPath || strpos($realPath, $realBase) !== 0) {
            return null;
        }
        return $realPath;
    }

    public static function serveDownload($fileId, $clientId = null, $isAdmin = false)
    {
        $file = self::get($fileId, $isAdmin ? null : $clientId);
        if (!$file) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }
        if (!$isAdmin && $clientId && (int) $file->client_id !== (int) $clientId) {
            http_response_code(403);
            exit('Acesso negado.');
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

    public static function commentsForFile($fileId)
    {
        return Capsule::table('mod_centralvix_site_comments')
            ->where('file_id', (int) $fileId)
            ->orderBy('id', 'asc')
            ->get();
    }

    public static function addComment($projectId, $fileId, $authorType, $authorId, $authorName, $message, $clientId = null)
    {
        $project = CentralVixSiteProjectService::get($projectId, $clientId);
        if (!$project) {
            return ['success' => false, 'message' => 'Projeto não encontrado.'];
        }
        $msg = CentralVixSiteSecurity::sanitizeText($message, 2000);
        if ($msg === '') {
            return ['success' => false, 'message' => 'Comentário vazio.'];
        }
        if ($fileId) {
            $file = self::get($fileId, $clientId);
            if (!$file || (int) $file->project_id !== (int) $projectId) {
                return ['success' => false, 'message' => 'Arquivo inválido.'];
            }
        }
        $id = Capsule::table('mod_centralvix_site_comments')->insertGetId([
            'project_id' => (int) $projectId,
            'file_id' => $fileId ? (int) $fileId : null,
            'author_type' => $authorType,
            'author_id' => (int) $authorId,
            'author_name' => CentralVixSiteSecurity::sanitizeText($authorName, 150),
            'message' => $msg,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        CentralVixSiteTimelineService::add($projectId, 'comment', 'Novo comentário', $msg, $authorType, $authorId, $authorName);
        return ['success' => true, 'id' => $id];
    }
}
