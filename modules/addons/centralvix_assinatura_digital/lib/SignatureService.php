<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixSignSignatureService
{
    public static function validateToken($token, $documentId = 0)
    {
        if (strlen($token) < 32) {
            return ['success' => false, 'message' => 'Token inválido.'];
        }
        $hash = CentralVixSignSecurity::hashToken($token);
        $q = Capsule::table('mod_centralvix_sign_requests')
            ->where('token_hash', $hash)
            ->where('status', 'active')
            ->where('expires_at', '>', date('Y-m-d H:i:s'));
        if ($documentId) {
            $q->where('document_id', (int) $documentId);
        }
        $req = $q->first();
        if (!$req) {
            return ['success' => false, 'message' => 'Token expirado ou inválido.'];
        }
        return ['success' => true, 'request' => $req, 'client_id' => (int) $req->client_id, 'document_id' => (int) $req->document_id];
    }

    public static function sign($documentId, $clientId, array $data)
    {
        $doc = CentralVixSignDocumentService::get($documentId, $clientId);
        if (!$doc) {
            return ['success' => false, 'message' => 'Documento não encontrado.'];
        }
        if (!in_array($doc->status, ['enviado', 'visualizado'], true)) {
            return ['success' => false, 'message' => 'Este documento não está disponível para assinatura.'];
        }
        if ($doc->expires_at && $doc->expires_at < date('Y-m-d H:i:s')) {
            Capsule::table('mod_centralvix_sign_documents')->where('id', $documentId)->update(['status' => 'expirado']);
            return ['success' => false, 'message' => 'Documento expirado.'];
        }
        if (empty($data['accept_terms'])) {
            return ['success' => false, 'message' => 'É necessário aceitar o documento.'];
        }
        if (!CentralVixSignDocumentService::verifyIntegrity($doc)) {
            return ['success' => false, 'message' => 'Integridade do documento inválida.'];
        }

        $client = Capsule::table('tblclients')->where('id', $clientId)->first();
        $signerName = CentralVixSignModule::clientName($clientId);
        $docIdField = CentralVixSignSecurity::sanitizeText($data['signer_document'] ?? '', 50);
        if ($docIdField === '' && $client) {
            $docIdField = CentralVixSignSecurity::sanitizeText($client->companyname ?? '', 50);
        }

        $dir = CentralVixSignDocumentService::docDir($documentId);
        $signaturePath = null;
        if (!empty($data['signature_data']) && preg_match('#^data:image/png;base64,#', $data['signature_data'])) {
            $raw = base64_decode(substr($data['signature_data'], strpos($data['signature_data'], ',') + 1));
            if ($raw && strlen($raw) < 500000) {
                $signaturePath = $dir . '/signature.png';
                file_put_contents($signaturePath, $raw);
            }
        }

        $evidence = [
            'accept_terms' => true,
            'accept_text' => 'Li e concordo com o documento apresentado.',
            'signer_name' => $signerName,
            'signer_document' => $docIdField ?: 'Não informado',
            'document_hash' => $doc->document_hash,
            'document_version' => (int) $doc->version,
            'transaction_id' => $doc->transaction_id,
            'has_drawn_signature' => (bool) $signaturePath,
            'provider' => (new CentralVixSignNullProvider())->signDocument($dir . '/original.pdf', [
                'name' => $signerName,
            ], []),
        ];

        CentralVixSignEvidenceService::record($documentId, $clientId, 'signed', 'Documento assinado eletronicamente', $evidence);

        $receiptPath = CentralVixSignPdfService::generateReceipt($doc, $client, $evidence);
        $signedCopyPath = CentralVixSignPdfService::generateSignedCopy($doc, $client, $evidence, $signaturePath);

        Capsule::table('mod_centralvix_sign_documents')->where('id', $documentId)->update([
            'status' => 'assinado',
            'signed_at' => date('Y-m-d H:i:s'),
            'signer_name' => $signerName,
            'signer_document' => CentralVixSignSecurity::sanitizeText($docIdField, 50),
            'signer_ip' => CentralVixSignSecurity::clientIp(),
            'receipt_filename' => $receiptPath ? 'receipt.pdf' : null,
            'signed_copy_filename' => $signedCopyPath ? 'signed_copy.pdf' : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        Capsule::table('mod_centralvix_sign_requests')
            ->where('document_id', $documentId)
            ->where('status', 'active')
            ->update(['status' => 'used', 'used_at' => date('Y-m-d H:i:s')]);

        CentralVixSignNotificationService::documentSigned($doc, $client);
        return ['success' => true, 'transaction_id' => $doc->transaction_id];
    }

    public static function reject($documentId, $clientId, $reason)
    {
        $doc = CentralVixSignDocumentService::get($documentId, $clientId);
        if (!$doc || !in_array($doc->status, ['enviado', 'visualizado'], true)) {
            return ['success' => false, 'message' => 'Documento não disponível.'];
        }
        $reason = CentralVixSignSecurity::sanitizeText($reason, 1000);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Informe o motivo da recusa.'];
        }

        CentralVixSignEvidenceService::record($documentId, $clientId, 'rejected', 'Documento recusado: ' . $reason, [
            'reason' => $reason,
        ]);

        Capsule::table('mod_centralvix_sign_documents')->where('id', $documentId)->update([
            'status' => 'recusado',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Capsule::table('mod_centralvix_sign_requests')->where('document_id', $documentId)->update(['status' => 'revoked']);

        return ['success' => true];
    }
}
