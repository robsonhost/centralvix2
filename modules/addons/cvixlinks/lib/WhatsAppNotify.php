<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CvixLinksWhatsApp
{
    protected static $core = null;

    public static function loadNotificazap()
    {
        if (class_exists('notificazapCore')) {
            return true;
        }
        $dir = ROOTDIR . '/modules/addons/notificazap';
        if (!is_file($dir . '/config.php')) {
            return false;
        }
        try {
            require_once $dir . '/config.php';
            if (is_file($dir . '/lib/notificazapCore.php')) {
                require_once $dir . '/lib/notificazapCore.php';
            }
            if (is_file($dir . '/lib/notificaWhatsApp.php')) {
                require_once $dir . '/lib/notificaWhatsApp.php';
            }
        } catch (Throwable $e) {
            return false;
        }
        return class_exists('notificazapCore');
    }

    public static function core()
    {
        if (self::$core !== null) {
            return self::$core;
        }
        if (!self::loadNotificazap()) {
            return null;
        }
        try {
            self::$core = new notificazapCore();
        } catch (Throwable $e) {
            self::$core = false;
        }
        return self::$core instanceof notificazapCore ? self::$core : null;
    }

    public static function clientOptedIn($core, $clientId)
    {
        try {
            $fieldId = $core->NOTIFICAZAP_Pega_Info_tbladdonmodules('addon_notificazap_receber_whatsapp');
            if (!$fieldId) {
                return true;
            }
            return $core->NOTIFICAZAP_Pega_Info_tblcustomfieldsvalues($fieldId, $clientId) === 'Sim';
        } catch (Throwable $e) {
            return true;
        }
    }

    public static function sendToClient($client, $message)
    {
        try {
            $core = self::core();
            if (!$core || !$client) {
                return false;
            }
            if (!self::clientOptedIn($core, $client->id)) {
                return false;
            }
            if (defined('NZURLSERVIDORWHATSAPP') && !$core->NOTIFICAZAP_Servidor_WhatsApp_Online(NZURLSERVIDORWHATSAPP)) {
                $core->NOTIFICAZAP_LogWarning('[CVIXLINKS] Servidor do WhatsApp está offline.');
                return false;
            }
            if (defined('NZSITEMODULESPAY') && $core->NOTIFICAZAP_ModulesPay_Online(NZSITEMODULESPAY)) {
                $license = NOTIFICAZAP_check_license($core->NOTIFICAZAP_Licenca());
                if (($license['status'] ?? '') !== 'Active') {
                    return false;
                }
            }
            $whatsapp = new notificaWhatsAppMpay(
                $core->NOTIFICAZAP_Server(),
                $core->NOTIFICAZAP_Descriptografar($core->NOTIFICAZAP_KEY()),
                $core->NOTIFICAZAP_Token_Geral(),
                $core->NOTIFICAZAP_Telefone_Origem()
            );
            if (($whatsapp->getInformation()['status'] ?? 0) !== 200 || ($whatsapp->connectionStatus()['instance']['state'] ?? '') !== 'open') {
                return false;
            }
            $phone = $core->NOTIFICAZAP_AdjustNumber($client);
            $check = $whatsapp->Valida_Number_WhatsApp($phone);
            if (!($check[0]['exists'] ?? null)) {
                $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, '[CVIXLINKS] Telefone ' . $phone . ' não é WhatsApp');
                return false;
            }
            $retorno = $core->NOTIFICAZAP_EnviarMensagem($phone, $message);
            if (($retorno['status'] ?? '') === 'success') {
                $core->NOTIFICAZAP_CadastrarLogWhatsapp($client->id, $message, 'sendmessage', 'cvixlinks');
                return true;
            }
            $core->NOTIFICAZAP_CadastrarLogFalhas($client->id, '[CVIXLINKS] Falha ao enviar: ' . json_encode($retorno));
        } catch (Throwable $e) {
            if (self::$core && method_exists(self::$core, 'NOTIFICAZAP_LogWarning')) {
                self::$core->NOTIFICAZAP_LogWarning('[CVIXLINKS] Erro: ' . $e->getMessage());
            }
        }
        return false;
    }

    public static function buildInvoiceMessage($client, $invoiceId, $amount, $dueDate)
    {
        $name = trim((string) $client->firstname);
        $systemUrl = CvixLinksService::systemUrl();
        return "Olá {$name}, aqui é da CENTRALVIX!\n"
            . "Sua fatura #{$invoiceId} de R$ " . CvixLinksService::formatMoney($amount)
            . " com vencimento em " . date('d/m/Y', strtotime($dueDate)) . " foi gerada com sucesso.\n"
            . "Acesse para pagar: {$systemUrl}/viewinvoice.php?id={$invoiceId}\n"
            . "Dúvidas? É só responder por aqui.";
    }

    public static function buildSignupMessage($client, $amount, $dueDay, $invoiceId = null)
    {
        $name = trim((string) $client->firstname);
        $msg = "Olá {$name}, bem-vindo(a) à CENTRALVIX!\n"
            . "Seu cadastro foi concluído pelo nosso convite: mensalidade de R$ " . CvixLinksService::formatMoney($amount)
            . " com vencimento todo dia {$dueDay}.\n";
        if ($invoiceId) {
            $msg .= "Sua primeira fatura (#{$invoiceId}) já está disponível na área do cliente.\n";
        }
        $msg .= "Dúvidas? É só responder por aqui.";
        return $msg;
    }
}
