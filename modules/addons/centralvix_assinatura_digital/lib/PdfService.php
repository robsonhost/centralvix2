<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixSignPdfService
{
    public static function generateReceipt($doc, $client, array $evidence)
    {
        $dir = CentralVixSignDocumentService::docDir($doc->id);
        $path = $dir . '/receipt.pdf';
        $lines = [
            'COMPROVANTE DE ACEITE ELETRONICO',
            'CentralVix Assinatura Digital',
            '',
            'AVISO: Este comprovante registra evidencias tecnicas de aceite.',
            'Nao constitui certificacao ICP-Brasil nem garantia juridica.',
            '',
            'ID Transacao: ' . $doc->transaction_id,
            'Documento: ' . $doc->title,
            'Versao: ' . (int) $doc->version,
            'Hash SHA-256: ' . $doc->document_hash,
            '',
            'Signatario: ' . ($evidence['signer_name'] ?? CentralVixSignModule::clientName($doc->client_id)),
            'Identificacao: ' . ($evidence['signer_document'] ?? 'N/I'),
            'E-mail: ' . ($client->email ?? ''),
            '',
            'Data/Hora: ' . date('d/m/Y H:i:s'),
            'IP: ' . CentralVixSignSecurity::clientIp(),
            'User-Agent: ' . mb_substr(CentralVixSignSecurity::userAgent(), 0, 120),
            '',
            'Aceite: Li e concordo com o documento apresentado.',
            'Assinatura desenhada: ' . (!empty($evidence['has_drawn_signature']) ? 'Sim (anexo)' : 'Nao'),
        ];
        self::writeSimplePdf($path, $lines);
        return is_file($path) ? $path : null;
    }

    public static function generateSignedCopy($doc, $client, array $evidence, $signaturePath = null)
    {
        $original = CentralVixSignDocumentService::docDir($doc->id) . '/original.pdf';
        if (!is_file($original)) {
            return null;
        }
        $dest = CentralVixSignDocumentService::docDir($doc->id) . '/signed_copy.pdf';
        copy($original, $dest);

        $appendix = CentralVixSignDocumentService::docDir($doc->id) . '/appendix.pdf';
        $appendLines = [
            'ANEXO DE ASSINATURA - COPIA NAO ALTERADA DO ORIGINAL',
            'O arquivo original.pdf permanece intacto no storage.',
            '',
            'ID: ' . $doc->transaction_id,
            'Hash original: ' . $doc->document_hash,
            'Assinado em: ' . date('d/m/Y H:i:s'),
            'Por: ' . ($evidence['signer_name'] ?? ''),
        ];
        if ($signaturePath && is_file($signaturePath)) {
            $appendLines[] = 'Assinatura desenhada registrada separadamente.';
        }
        self::writeSimplePdf($appendix, $appendLines);

        return is_file($dest) ? $dest : null;
    }

    protected static function writeSimplePdf($path, array $lines)
    {
        $text = implode("\n", $lines);
        $text = self::pdfEscape($text);
        $objects = [];
        $objects[] = "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
        $objects[] = "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
        $content = "BT /F1 10 Tf 50 750 Td 14 TL\n";
        foreach (explode("\n", $text) as $line) {
            $content .= '(' . self::pdfEscape($line) . ") '\n";
        }
        $content .= "ET";
        $streamLen = strlen($content);
        $objects[] = "4 0 obj << /Length $streamLen >> stream\n$content\nendstream endobj\n";
        $objects[] = "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >> endobj\n";
        $objects[] = "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= $obj;
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . count($offsets) . "\n0000000000 65535 f \n";
        for ($i = 1; $i < count($offsets); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size " . count($offsets) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        @file_put_contents($path, $pdf);
    }

    protected static function pdfEscape($s)
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }
}
