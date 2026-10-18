<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSignDocumentService
{
    const STATUSES = [
        'rascunho' => 'Rascunho',
        'enviado' => 'Enviado',
        'visualizado' => 'Visualizado',
        'assinado' => 'Assinado',
        'recusado' => 'Recusado',
        'expirado' => 'Expirado',
        'cancelado' => 'Cancelado',
    ];

    public static function statusLabel($s)
    {
        return self::STATUSES[$s] ?? $s;
    }

    public static function ensureStorage()
    {
        $root = CentralVixSignModule::storagePath();
        if (!is_dir($root)) {
            @mkdir($root, 0755, true);
        }
        $ht = $root . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\nDeny from all\n</FilesMatch>\n");
        }
    }

    public static function docDir($documentId)
    {
        return CentralVixSignModule::storagePath() . '/d' . (int) $documentId;
    }

    public static function dashboardStats()
    {
        $pending = Capsule::table('mod_centralvix_sign_documents')->whereIn('status', ['enviado', 'visualizado'])->count();
        $signed = Capsule::table('mod_centralvix_sign_documents')->where('status', 'assinado')->count();
        $rejected = Capsule::table('mod_centralvix_sign_documents')->where('status', 'recusado')->count();
        $expired = Capsule::table('mod_centralvix_sign_documents')->where('status', 'expirado')->count();
        return compact('pending', 'signed', 'rejected', 'expired');
    }

    public static function listAll($limit = 200)
    {
        return Capsule::table('mod_centralvix_sign_documents as d')
            ->leftJoin('tblclients as c', 'c.id', '=', 'd.client_id')
            ->select('d.*', Capsule::raw("CONCAT(c.firstname,' ',c.lastname) as client_name"))
            ->orderBy('d.id', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function listByClient($clientId)
    {
        return Capsule::table('mod_centralvix_sign_documents')
            ->where('client_id', (int) $clientId)
            ->whereNotIn('status', ['rascunho', 'cancelado'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public static function get($id, $clientId = null)
    {
        $q = Capsule::table('mod_centralvix_sign_documents')->where('id', (int) $id);
        if ($clientId !== null) {
            $q->where('client_id', (int) $clientId);
        }
        return $q->first();
    }

    public static function generateTransactionId()
    {
        return 'CVIX-SIGN-' . strtoupper(bin2hex(random_bytes(8))) . '-' . time();
    }

    public static function createFromUpload($clientId, $title, $templateId = 0, $expiresDays = 30)
    {
        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Envie um arquivo PDF válido.'];
        }
        $file = $_FILES['document'];
        $maxMb = (int) CentralVixSignModule::setting('max_upload_mb', 15);
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['success' => false, 'message' => 'PDF excede ' . $maxMb . ' MB.'];
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') {
            return ['success' => false, 'message' => 'Somente PDF é permitido.'];
        }

        $title = CentralVixSignSecurity::sanitizeText($title, 200);
        if (!$clientId || $title === '') {
            return ['success' => false, 'message' => 'Cliente e título são obrigatórios.'];
        }

        $txId = self::generateTransactionId();
        $id = Capsule::table('mod_centralvix_sign_documents')->insertGetId([
            'client_id' => (int) $clientId,
            'template_id' => (int) $templateId,
            'title' => $title,
            'original_filename' => CentralVixSignSecurity::sanitizeText($file['name'], 255),
            'stored_filename' => 'original.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => (int) $file['size'],
            'document_hash' => '',
            'version' => 1,
            'status' => 'rascunho',
            'transaction_id' => $txId,
            'expires_at' => date('Y-m-d H:i:s', strtotime('+' . (int) $expiresDays . ' days')),
            'created_by_admin' => (int) ($_SESSION['adminid'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        self::ensureStorage();
        $dir = self::docDir($id);
        @mkdir($dir, 0755, true);
        $dest = $dir . '/original.pdf';
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Capsule::table('mod_centralvix_sign_documents')->where('id', $id)->delete();
            return ['success' => false, 'message' => 'Falha ao salvar PDF.'];
        }

        $hash = CentralVixSignHashService::fileHash($dest);
        Capsule::table('mod_centralvix_sign_documents')->where('id', $id)->update(['document_hash' => $hash]);

        CentralVixSignEvidenceService::record($id, (int) $clientId, 'document_created', 'Documento criado', [
            'hash' => $hash,
            'version' => 1,
        ]);

        return ['success' => true, 'id' => $id, 'transaction_id' => $txId];
    }

    public static function sendForSignature($documentId)
    {
        $doc = self::get($documentId);
        if (!$doc || $doc->status === 'cancelado') {
            return ['success' => false, 'message' => 'Documento não encontrado.'];
        }
        if (!self::verifyIntegrity($doc)) {
            return ['success' => false, 'message' => 'Integridade do documento comprometida.'];
        }

        $token = CentralVixSignSecurity::generateToken(32);
        $tokenHash = CentralVixSignSecurity::hashToken($token);
        $expires = $doc->expires_at ?: date('Y-m-d H:i:s', strtotime('+30 days'));

        Capsule::table('mod_centralvix_sign_requests')->insert([
            'document_id' => (int) $documentId,
            'client_id' => (int) $doc->client_id,
            'token_hash' => $tokenHash,
            'status' => 'active',
            'expires_at' => $expires,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        Capsule::table('mod_centralvix_sign_documents')->where('id', $documentId)->update([
            'status' => 'enviado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixSignEvidenceService::record($documentId, (int) $doc->client_id, 'sent', 'Solicitação de assinatura enviada');
        CentralVixSignNotificationService::signatureRequested($doc);

        return [
            'success' => true,
            'token' => $token,
            'link' => CentralVixSignModule::systemUrl() . '/modules/addons/centralvix_assinatura_digital/public/sign.php?t=' . urlencode($token),
        ];
    }

    public static function verifyIntegrity($doc)
    {
        $path = self::docDir($doc->id) . '/original.pdf';
        return CentralVixSignHashService::verifyFile($path, $doc->document_hash);
    }

    public static function resolveFilePath($doc, $type = 'original')
    {
        $dir = realpath(self::docDir($doc->id));
        if (!$dir) {
            return null;
        }
        $map = [
            'original' => 'original.pdf',
            'receipt' => 'receipt.pdf',
            'signed_copy' => 'signed_copy.pdf',
            'signature' => 'signature.png',
        ];
        $name = $map[$type] ?? null;
        if (!$name) {
            return null;
        }
        $path = $dir . '/' . $name;
        $real = realpath($path);
        if (!$real || strpos($real, $dir) !== 0) {
            return null;
        }
        return is_file($real) ? $real : null;
    }

    public static function serveFile($documentId, $type, $clientId = null, $token = null)
    {
        $doc = self::get($documentId, $clientId);
        if (!$doc && $token) {
            $req = CentralVixSignSignatureService::validateToken($token, $documentId);
            if (!$req['success']) {
                http_response_code(403);
                exit('Acesso negado.');
            }
            $doc = self::get($documentId, (int) $req['client_id']);
        }
        if (!$doc) {
            http_response_code(404);
            exit('Documento não encontrado.');
        }
        if ($clientId !== null && (int) $doc->client_id !== (int) $clientId) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $path = self::resolveFilePath($doc, $type);
        if (!$path) {
            http_response_code(404);
            exit('Arquivo não disponível.');
        }

        if ($type === 'original' && !self::verifyIntegrity($doc)) {
            http_response_code(409);
            exit('Documento alterado — integridade inválida.');
        }

        $mime = $type === 'original' ? 'application/pdf' : (mime_content_type($path) ?: 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($type === 'original' ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', basename($path)) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public static function markViewed($documentId, $clientId)
    {
        $doc = self::get($documentId, $clientId);
        if (!$doc || !in_array($doc->status, ['enviado', 'visualizado'], true)) {
            return;
        }
        if ($doc->status === 'enviado') {
            Capsule::table('mod_centralvix_sign_documents')->where('id', $documentId)->update([
                'status' => 'visualizado',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            CentralVixSignEvidenceService::record($documentId, $clientId, 'viewed', 'Documento visualizado pelo signatário');
        }
    }

    public static function cancel($documentId)
    {
        Capsule::table('mod_centralvix_sign_documents')->where('id', (int) $documentId)->update([
            'status' => 'cancelado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Capsule::table('mod_centralvix_sign_requests')->where('document_id', (int) $documentId)->update(['status' => 'revoked']);
        return ['success' => true];
    }

    public static function checkExpired()
    {
        $now = date('Y-m-d H:i:s');
        $ids = Capsule::table('mod_centralvix_sign_documents')
            ->whereIn('status', ['enviado', 'visualizado'])
            ->where('expires_at', '<', $now)
            ->pluck('id');
        foreach ($ids as $id) {
            Capsule::table('mod_centralvix_sign_documents')->where('id', $id)->update(['status' => 'expirado', 'updated_at' => $now]);
            CentralVixSignEvidenceService::record($id, 0, 'expired', 'Documento expirado');
        }
    }

    public static function listTemplates()
    {
        return Capsule::table('mod_centralvix_sign_templates')->where('active', 1)->orderBy('name')->get();
    }

    public static function allTemplates()
    {
        return Capsule::table('mod_centralvix_sign_templates')->orderBy('name')->get();
    }

    public static function saveTemplate(array $data, $id = 0)
    {
        $row = [
            'name' => CentralVixSignSecurity::sanitizeText($data['name'] ?? '', 100),
            'title' => CentralVixSignSecurity::sanitizeText($data['title'] ?? '', 200),
            'description' => CentralVixSignSecurity::sanitizeText($data['description'] ?? '', 2000),
            'active' => !empty($data['active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id > 0) {
            Capsule::table('mod_centralvix_sign_templates')->where('id', $id)->update($row);
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $id = Capsule::table('mod_centralvix_sign_templates')->insertGetId($row);
        }
        return ['success' => true, 'id' => $id];
    }
}
