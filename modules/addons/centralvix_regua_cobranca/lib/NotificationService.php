<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixReguaNotificationService
{
    public static function sendForInvoiceRule($invoice, $rule, $template)
    {
        $client = CentralVixReguaInvoiceService::getClient($invoice->userid);
        if (!$client) {
            return ['email' => ['success' => false, 'message' => 'Cliente não encontrado']];
        }

        $vars = CentralVixReguaInvoiceService::buildPlaceholderVars($invoice, $client);
        $results = [];

        if ($rule->send_email) {
            if (CentralVixReguaLogger::wasSent($invoice->id, $rule->id, 'email')) {
                $results['email'] = ['success' => false, 'message' => 'Já enviado (idempotência).', 'skipped' => true];
            } else {
                $subject = CentralVixReguaTemplateService::render($template->subject, $vars);
                $body = CentralVixReguaTemplateService::render($template->body_email, $vars);
                $results['email'] = self::sendEmail($client, $subject, $body);
                CentralVixReguaLogger::log(
                    $invoice->id,
                    $rule->id,
                    $client->id,
                    'email',
                    $results['email']['success'] ? 'success' : 'failed',
                    $results['email']['message'] ?? '',
                    $subject
                );
            }
        }

        if ($rule->send_whatsapp) {
            if (CentralVixReguaLogger::wasSent($invoice->id, $rule->id, 'whatsapp')) {
                $results['whatsapp'] = ['success' => false, 'message' => 'Já enviado (idempotência).', 'skipped' => true];
            } else {
                $body = CentralVixReguaTemplateService::render($template->body_whatsapp, $vars);
                $phone = $client->phonenumber ?? '';
                $results['whatsapp'] = CentralVixReguaWhatsAppService::send($phone, $body);
                CentralVixReguaLogger::log(
                    $invoice->id,
                    $rule->id,
                    $client->id,
                    'whatsapp',
                    $results['whatsapp']['success'] ? 'success' : 'failed',
                    ($results['whatsapp']['message'] ?? '') . ' ' . ($results['whatsapp']['response'] ?? ''),
                    mb_substr($body, 0, 200)
                );
            }
        }

        return $results;
    }

    public static function sendPaymentConfirmation($invoiceId)
    {
        if (CentralVixReguaModule::setting('send_payment_confirmation', '') !== 'on') {
            return false;
        }

        $templateId = (int) CentralVixReguaModule::setting('payment_template_id', CentralVixReguaModule::dbSetting('payment_template_id', '0'));
        if (!$templateId) {
            $tpl = Capsule::table('mod_centralvix_regua_templates')->where('is_payment_confirmation', 1)->first();
            if (!$tpl) {
                return false;
            }
            $template = $tpl;
        } else {
            $template = CentralVixReguaTemplateService::get($templateId);
        }

        if (!$template) {
            return false;
        }

        $invoice = CentralVixReguaInvoiceService::getInvoice($invoiceId);
        if (!$invoice) {
            return false;
        }

        $key = 'paid_confirm_' . $invoiceId;
        if (CentralVixReguaModule::dbSetting($key, '') === '1') {
            return false;
        }

        $client = CentralVixReguaInvoiceService::getClient($invoice->userid);
        if (!$client) {
            return false;
        }

        $vars = CentralVixReguaInvoiceService::buildPlaceholderVars($invoice, $client);
        $subject = CentralVixReguaTemplateService::render($template->subject, $vars);
        $bodyEmail = CentralVixReguaTemplateService::render($template->body_email, $vars);
        $bodyWa = CentralVixReguaTemplateService::render($template->body_whatsapp, $vars);

        self::sendEmail($client, $subject, $bodyEmail);
        if (CentralVixReguaModule::setting('notify_whatsapp_paid', '') === 'on') {
            CentralVixReguaWhatsAppService::send($client->phonenumber ?? '', $bodyWa);
        }

        CentralVixReguaModule::setDbSetting($key, '1');
        CentralVixReguaLogger::log($invoiceId, 0, $client->id, 'email', 'success', 'Pagamento confirmado', $subject, 'paid_' . $invoiceId);

        return true;
    }

    private static function sendEmail($client, $subject, $bodyHtml)
    {
        if (empty($client->email) || !filter_var($client->email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'E-mail do cliente inválido.'];
        }

        if (function_exists('localAPI')) {
            try {
                $result = localAPI('SendEmail', [
                    'id' => (int) $client->id,
                    'customtype' => 'general',
                    'customsubject' => $subject,
                    'custommessage' => $bodyHtml,
                ]);
                if (is_array($result) && ($result['result'] ?? '') === 'success') {
                    return ['success' => true, 'message' => 'E-mail enviado via WHMCS.'];
                }
            } catch (Throwable $e) {
                // fallback abaixo
            }
        }

        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $sent = @mail($client->email, $subject, $bodyHtml, $headers);
        return [
            'success' => (bool) $sent,
            'message' => $sent ? 'E-mail enviado.' : 'Falha ao enviar e-mail.',
        ];
    }
}
