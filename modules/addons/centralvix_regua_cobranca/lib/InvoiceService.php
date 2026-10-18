<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixReguaInvoiceService
{
    public static function getInvoice($id)
    {
        return Capsule::table('tblinvoices')->where('id', (int) $id)->first();
    }

    public static function getClient($id)
    {
        return Capsule::table('tblclients')->where('id', (int) $id)->first();
    }

    public static function formatMoney($amount, $clientId = 0)
    {
        $symbol = 'R$';
        if ($clientId) {
            $client = self::getClient($clientId);
            if ($client && !empty($client->currency)) {
                $currency = Capsule::table('tblcurrencies')->where('id', $client->currency)->first();
                if ($currency && !empty($currency->prefix)) {
                    $symbol = $currency->prefix;
                }
            }
        }
        return $symbol . ' ' . number_format((float) $amount, 2, ',', '.');
    }

    public static function invoiceLink($invoiceId)
    {
        $base = CentralVixReguaModule::systemUrl();
        return $base ? $base . '/viewinvoice.php?id=' . (int) $invoiceId : '';
    }

    public static function paymentLink($invoiceId)
    {
        $base = CentralVixReguaModule::systemUrl();
        return $base ? $base . '/viewinvoice.php?id=' . (int) $invoiceId : '';
    }

    public static function daysOverdue($duedate)
    {
        $due = strtotime($duedate);
        $today = strtotime(date('Y-m-d'));
        if ($today <= $due) {
            return 0;
        }
        return (int) floor(($today - $due) / 86400);
    }

    public static function daysUntilDue($duedate)
    {
        $due = strtotime($duedate);
        $today = strtotime(date('Y-m-d'));
        if ($today >= $due) {
            return 0;
        }
        return (int) floor(($due - $today) / 86400);
    }

    public static function primaryProductName($invoiceId)
    {
        $item = Capsule::table('tblinvoiceitems')
            ->where('invoiceid', (int) $invoiceId)
            ->orderBy('id')
            ->first();
        if (!$item) {
            return '';
        }
        if ($item->type === 'Hosting' && $item->relid) {
            $hosting = Capsule::table('tblhosting')->where('id', $item->relid)->first();
            if ($hosting) {
                $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
                if ($product) {
                    return $product->name;
                }
            }
        }
        return $item->description ?: '';
    }

    public static function buildPlaceholderVars($invoice, $client = null)
    {
        if (!$client) {
            $client = self::getClient($invoice->userid);
        }
        $company = CentralVixReguaModule::setting('company_name', CentralVixReguaModule::lang('default_company', 'CentralVix'));
        $fullName = trim(($client->firstname ?? '') . ' ' . ($client->lastname ?? ''));

        return [
            'cliente' => $fullName,
            'nome' => $client->firstname ?? '',
            'empresa' => $company,
            'fatura' => (string) $invoice->id,
            'valor' => self::formatMoney($invoice->total, $invoice->userid),
            'vencimento' => date('d/m/Y', strtotime($invoice->duedate)),
            'dias_atraso' => self::daysOverdue($invoice->duedate),
            'link_fatura' => self::invoiceLink($invoice->id),
            'link_pagamento' => self::paymentLink($invoice->id),
            'produto' => self::primaryProductName($invoice->id),
        ];
    }

    public static function samplePlaceholderVars($invoiceId = 0)
    {
        if ($invoiceId) {
            $invoice = self::getInvoice($invoiceId);
            if ($invoice) {
                return self::buildPlaceholderVars($invoice);
            }
        }
        $base = CentralVixReguaModule::systemUrl() ?: 'https://exemplo.com.br';
        return [
            'cliente' => 'João Silva',
            'nome' => 'João',
            'empresa' => CentralVixReguaModule::setting('company_name', 'CentralVix'),
            'fatura' => '1234',
            'valor' => 'R$ 99,90',
            'vencimento' => date('d/m/Y', strtotime('+3 days')),
            'dias_atraso' => '0',
            'link_fatura' => $base . '/viewinvoice.php?id=1234',
            'link_pagamento' => $base . '/viewinvoice.php?id=1234',
            'produto' => 'Hospedagem Premium',
        ];
    }

    public static function matchesRuleFilters($invoice, $rule)
    {
        $client = self::getClient($invoice->userid);
        if (!$client) {
            return false;
        }

        $clientIds = json_decode($rule->client_ids ?: '[]', true) ?: [];
        if (!empty($clientIds) && !in_array((int) $client->id, array_map('intval', $clientIds), true)) {
            return false;
        }

        $clientGroupIds = json_decode($rule->client_group_ids ?: '[]', true) ?: [];
        if (!empty($clientGroupIds) && !in_array((int) $client->groupid, array_map('intval', $clientGroupIds), true)) {
            return false;
        }

        $productIds = json_decode($rule->product_ids ?: '[]', true) ?: [];
        $productGroupIds = json_decode($rule->product_group_ids ?: '[]', true) ?: [];

        if (empty($productIds) && empty($productGroupIds)) {
            return true;
        }

        $items = Capsule::table('tblinvoiceitems')->where('invoiceid', $invoice->id)->get();
        foreach ($items as $item) {
            if ($item->type === 'Hosting' && $item->relid) {
                $hosting = Capsule::table('tblhosting')->where('id', $item->relid)->first();
                if ($hosting) {
                    if (!empty($productIds) && in_array((int) $hosting->packageid, array_map('intval', $productIds), true)) {
                        return true;
                    }
                    if (!empty($productGroupIds)) {
                        $product = Capsule::table('tblproducts')->where('id', $hosting->packageid)->first();
                        if ($product && in_array((int) $product->gid, array_map('intval', $productGroupIds), true)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function matchesRuleTiming($invoice, $rule)
    {
        $today = date('Y-m-d');
        $due = date('Y-m-d', strtotime($invoice->duedate));
        $timing = $rule->timing;
        $days = (int) $rule->days_relative;

        if ($timing === 'before') {
            $target = date('Y-m-d', strtotime($due . ' -' . $days . ' days'));
            return $today === $target;
        }
        if ($timing === 'on') {
            return $today === $due;
        }
        if ($timing === 'after') {
            $target = date('Y-m-d', strtotime($due . ' +' . $days . ' days'));
            return $today === $target;
        }
        return false;
    }

    public static function getInvoicesForRule($rule)
    {
        $statuses = array_filter(array_map('trim', explode(',', $rule->invoice_status ?: 'Unpaid')));
        if (empty($statuses)) {
            $statuses = ['Unpaid'];
        }

        $invoices = Capsule::table('tblinvoices')
            ->whereIn('status', $statuses)
            ->get();

        $matched = [];
        foreach ($invoices as $invoice) {
            if (!self::matchesRuleTiming($invoice, $rule)) {
                continue;
            }
            if (!self::matchesRuleFilters($invoice, $rule)) {
                continue;
            }
            $matched[] = $invoice;
        }
        return $matched;
    }

    public static function listProducts()
    {
        return Capsule::table('tblproducts')->orderBy('name')->get(['id', 'name', 'gid']);
    }

    public static function listProductGroups()
    {
        return Capsule::table('tblproductgroups')->orderBy('name')->get(['id', 'name']);
    }

    public static function listClientGroups()
    {
        if (!Capsule::schema()->hasTable('tblclientgroups')) {
            return [];
        }
        return Capsule::table('tblclientgroups')->orderBy('groupname')->get(['id', 'groupname']);
    }
}
