<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixReguaExecutionService
{
    public static function run($force = false)
    {
        $globalFrom = CentralVixReguaModule::setting('global_time_from', '08:00');
        $globalTo = CentralVixReguaModule::setting('global_time_to', '20:00');

        if (!$force && !CentralVixReguaModule::isWithinTimeWindow($globalFrom, $globalTo)) {
            return [
                'success' => true,
                'skipped' => true,
                'message' => 'Fora da janela global de envio (' . $globalFrom . ' - ' . $globalTo . ').',
                'processed' => 0,
            ];
        }

        $rules = CentralVixReguaBillingRuleService::listAll(true);
        $processed = 0;
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $details = [];

        foreach ($rules as $rule) {
            $ruleFrom = substr($rule->time_from, 0, 5);
            $ruleTo = substr($rule->time_to, 0, 5);
            if (!$force && !CentralVixReguaModule::isWithinTimeWindow($ruleFrom, $ruleTo)) {
                continue;
            }

            $template = CentralVixReguaTemplateService::get($rule->template_id);
            if (!$template) {
                $details[] = ['rule' => $rule->name, 'error' => 'Template não encontrado'];
                continue;
            }

            $invoices = CentralVixReguaInvoiceService::getInvoicesForRule($rule);
            foreach ($invoices as $invoice) {
                if (in_array($invoice->status, ['Paid', 'Cancelled', 'Refunded'], true)) {
                    $skipped++;
                    continue;
                }

                $processed++;
                $result = CentralVixReguaNotificationService::sendForInvoiceRule($invoice, $rule, $template);

                foreach ($result as $channel => $r) {
                    if (!empty($r['skipped'])) {
                        $skipped++;
                        continue;
                    }
                    if (!empty($r['success'])) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }

                $details[] = [
                    'invoice_id' => $invoice->id,
                    'rule' => $rule->name,
                    'result' => $result,
                ];
            }
        }

        CentralVixReguaModule::setDbSetting('last_execution', date('Y-m-d H:i:s'));

        return [
            'success' => true,
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'details' => $details,
            'time' => date('Y-m-d H:i:s'),
        ];
    }
}
