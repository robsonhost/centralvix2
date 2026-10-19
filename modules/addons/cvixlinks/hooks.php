<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lib/LinksService.php';
require_once __DIR__ . '/lib/WhatsAppNotify.php';

add_hook('ClientAdd', 5, function ($vars) {
    $userId = (int) ($vars['userid'] ?? 0);
    $token = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($_COOKIE['cvixlk'] ?? ''));
    if ($userId <= 0 || $token === '') {
        return;
    }
    if (!CvixLinksService::hasTable()) {
        return;
    }

    try {
        $link = CvixLinksService::findActive($token, 'signup');
        if (!$link) {
            setcookie('cvixlk', '', time() - 3600, '/');
            return;
        }

        $client = WHMCS\User\Client::find($userId);
        if (!$client) {
            return;
        }

        $dueDate = CvixLinksService::nextDueDateForDay((int) $link->due_day);
        $description = trim((string) $link->description) !== ''
            ? $link->description
            : 'Mensalidade contratada (convite de cadastro)';

        [$invoiceId, $error] = CvixLinksService::createInvoice(
            $userId,
            (float) $link->amount,
            $dueDate,
            $description,
            'Fatura gerada automaticamente pelo convite de cadastro CentralVix.'
        );

        if ($invoiceId > 0) {
            CvixLinksService::markUsed($link->id, $invoiceId);
            logActivity('[CVIXLINKS] Convite ' . $link->token . ': fatura #' . $invoiceId . ' gerada para o cliente #' . $userId);
            if (!CvixLinksWhatsApp::sendToClient($client, CvixLinksWhatsApp::buildSignupMessage($client, (float) $link->amount, (int) $link->due_day, $invoiceId))) {
                logActivity('[CVIXLINKS] WhatsApp não enviado para o cliente #' . $userId . ' (opt-in ausente, instância offline ou notificazap inativo).');
            }
        } else {
            logActivity('[CVIXLINKS] Falha ao gerar fatura do convite ' . $link->token . ' (cliente #' . $userId . '): ' . $error);
        }
    } catch (Throwable $e) {
        logActivity('[CVIXLINKS] Erro no hook ClientAdd: ' . $e->getMessage());
    }

    setcookie('cvixlk', '', time() - 3600, '/');
});
