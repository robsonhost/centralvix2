<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixReguaTemplateService
{
    public static function listAll()
    {
        return Capsule::table('mod_centralvix_regua_templates')->orderBy('name')->get();
    }

    public static function get($id)
    {
        return Capsule::table('mod_centralvix_regua_templates')->where('id', (int) $id)->first();
    }

    public static function save(array $data, $id = 0)
    {
        $row = [
            'name' => CentralVixReguaSecurity::sanitizeText($data['name'] ?? '', 150),
            'subject' => CentralVixReguaSecurity::sanitizeText($data['subject'] ?? '', 200),
            'body_email' => CentralVixReguaSecurity::sanitizeHtml($data['body_email'] ?? '', 15000),
            'body_whatsapp' => CentralVixReguaSecurity::sanitizeText($data['body_whatsapp'] ?? '', 5000),
            'is_payment_confirmation' => !empty($data['is_payment_confirmation']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($id) {
            Capsule::table('mod_centralvix_regua_templates')->where('id', (int) $id)->update($row);
            return (int) $id;
        }
        $row['created_at'] = date('Y-m-d H:i:s');
        return (int) Capsule::table('mod_centralvix_regua_templates')->insertGetId($row);
    }

    public static function delete($id)
    {
        $inUse = Capsule::table('mod_centralvix_regua_rules')->where('template_id', (int) $id)->count();
        if ($inUse > 0) {
            return false;
        }
        Capsule::table('mod_centralvix_regua_templates')->where('id', (int) $id)->delete();
        return true;
    }

    public static function placeholders()
    {
        return [
            '{cliente}' => 'Nome completo do cliente',
            '{nome}' => 'Primeiro nome',
            '{empresa}' => 'Empresa CentralVix',
            '{fatura}' => 'Número da fatura',
            '{valor}' => 'Valor total formatado',
            '{vencimento}' => 'Data de vencimento',
            '{dias_atraso}' => 'Dias em atraso (0 se em dia)',
            '{link_fatura}' => 'Link para visualizar fatura',
            '{link_pagamento}' => 'Link de pagamento',
            '{produto}' => 'Produto/serviço principal',
        ];
    }

    public static function render($text, array $vars)
    {
        if ($text === '') {
            return '';
        }
        $map = [
            '{cliente}' => $vars['cliente'] ?? '',
            '{nome}' => $vars['nome'] ?? '',
            '{empresa}' => $vars['empresa'] ?? '',
            '{fatura}' => $vars['fatura'] ?? '',
            '{valor}' => $vars['valor'] ?? '',
            '{vencimento}' => $vars['vencimento'] ?? '',
            '{dias_atraso}' => (string) ($vars['dias_atraso'] ?? '0'),
            '{link_fatura}' => $vars['link_fatura'] ?? '',
            '{link_pagamento}' => $vars['link_pagamento'] ?? '',
            '{produto}' => $vars['produto'] ?? '',
        ];
        return str_replace(array_keys($map), array_values($map), $text);
    }

    public static function preview(array $templateData, $invoiceId = 0)
    {
        $vars = CentralVixReguaInvoiceService::samplePlaceholderVars($invoiceId);
        return [
            'subject' => self::render($templateData['subject'] ?? '', $vars),
            'body_email' => self::render($templateData['body_email'] ?? '', $vars),
            'body_whatsapp' => self::render($templateData['body_whatsapp'] ?? '', $vars),
            'vars' => $vars,
        ];
    }

    public static function seedDefaults()
    {
        if (Capsule::table('mod_centralvix_regua_templates')->count() > 0) {
            return;
        }

        $body = "Olá {nome},\n\nLembramos que a fatura #{fatura} no valor de {valor} vence em {vencimento}.\n\nAcesse: {link_pagamento}\n\n{empresa}";

        $templateId = Capsule::table('mod_centralvix_regua_templates')->insertGetId([
            'name' => 'Lembrete padrão',
            'subject' => 'Lembrete de fatura #{fatura} — {empresa}',
            'body_email' => '<p>Olá <strong>{nome}</strong>,</p><p>A fatura <strong>#{fatura}</strong> no valor de <strong>{valor}</strong> vence em <strong>{vencimento}</strong>.</p><p><a href="{link_pagamento}">Pagar agora</a></p><p>{empresa}</p>',
            'body_whatsapp' => $body,
            'is_payment_confirmation' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $paidId = Capsule::table('mod_centralvix_regua_templates')->insertGetId([
            'name' => 'Pagamento confirmado',
            'subject' => 'Pagamento confirmado — Fatura #{fatura}',
            'body_email' => '<p>Olá {nome},</p><p>Confirmamos o pagamento da fatura #{fatura} no valor de {valor}.</p><p>Obrigado!<br>{empresa}</p>',
            'body_whatsapp' => "Olá {nome}, pagamento da fatura #{fatura} ({valor}) confirmado. Obrigado! {empresa}",
            'is_payment_confirmation' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        CentralVixReguaModule::setDbSetting('payment_template_id', (string) $paidId);

        $rules = [
            ['name' => '7 dias antes', 'timing' => 'before', 'days' => 7, 'status' => 'Unpaid'],
            ['name' => '5 dias antes', 'timing' => 'before', 'days' => 5, 'status' => 'Unpaid'],
            ['name' => '3 dias antes', 'timing' => 'before', 'days' => 3, 'status' => 'Unpaid'],
            ['name' => '1 dia antes', 'timing' => 'before', 'days' => 1, 'status' => 'Unpaid'],
            ['name' => 'No vencimento', 'timing' => 'on', 'days' => 0, 'status' => 'Unpaid'],
            ['name' => '1 dia atrasado', 'timing' => 'after', 'days' => 1, 'status' => 'Unpaid,Payment Pending,Overdue'],
            ['name' => '3 dias atrasado', 'timing' => 'after', 'days' => 3, 'status' => 'Unpaid,Payment Pending,Overdue'],
            ['name' => '5 dias atrasado', 'timing' => 'after', 'days' => 5, 'status' => 'Unpaid,Payment Pending,Overdue'],
            ['name' => '10 dias atrasado', 'timing' => 'after', 'days' => 10, 'status' => 'Unpaid,Payment Pending,Overdue'],
        ];

        $order = 0;
        foreach ($rules as $r) {
            Capsule::table('mod_centralvix_regua_rules')->insert([
                'name' => $r['name'],
                'active' => 1,
                'days_relative' => $r['days'],
                'timing' => $r['timing'],
                'invoice_status' => $r['status'],
                'send_email' => 1,
                'send_whatsapp' => 0,
                'template_id' => $templateId,
                'time_from' => '08:00:00',
                'time_to' => '20:00:00',
                'product_ids' => '[]',
                'product_group_ids' => '[]',
                'client_ids' => '[]',
                'client_group_ids' => '[]',
                'display_order' => $order++,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
