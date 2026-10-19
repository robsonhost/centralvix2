<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CvixLinksService
{
    const TABLE = 'mod_cvixlinks';

    public static function ensureTable()
    {
        if (!WHMCS\Database\Capsule::schema()->hasTable(self::TABLE)) {
            WHMCS\Database\Capsule::schema()->create(self::TABLE, function ($table) {
                $table->increments('id');
                $table->string('token', 64)->unique();
                $table->string('type', 20);
                $table->integer('client_id')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->boolean('fixed_amount')->default(0);
                $table->tinyInteger('due_day')->nullable();
                $table->string('description', 250)->nullable();
                $table->string('status', 20)->default('active');
                $table->integer('invoice_id')->nullable();
                $table->date('expires_at')->nullable();
                $table->integer('created_by')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('used_at')->nullable();
                $table->index('status');
                $table->index('type');
            });
        }
    }

    public static function hasTable()
    {
        try {
            return WHMCS\Database\Capsule::schema()->hasTable(self::TABLE);
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function generateToken()
    {
        return bin2hex(random_bytes(16));
    }

    public static function create(array $data)
    {
        self::ensureTable();
        $now = date('Y-m-d H:i:s');
        WHMCS\Database\Capsule::table(self::TABLE)->insert([
            'token' => self::generateToken(),
            'type' => $data['type'],
            'client_id' => $data['client_id'] ?? null,
            'amount' => $data['amount'] ?? null,
            'fixed_amount' => !empty($data['fixed_amount']) ? 1 : 0,
            'due_day' => $data['due_day'] ?? null,
            'description' => $data['description'] ?? null,
            'status' => 'active',
            'expires_at' => $data['expires_at'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $now,
        ]);
        return WHMCS\Database\Capsule::table(self::TABLE)->orderBy('id', 'desc')->first();
    }

    public static function findByToken($token)
    {
        if (!self::hasTable()) {
            return null;
        }
        $token = preg_replace('/[^a-zA-Z0-9]/', '', (string) $token);
        if ($token === '') {
            return null;
        }
        return WHMCS\Database\Capsule::table(self::TABLE)->where('token', $token)->first();
    }

    public static function findActive($token, $type = null)
    {
        $row = self::findByToken($token);
        if (!$row || $row->status !== 'active') {
            return null;
        }
        if ($type !== null && $row->type !== $type) {
            return null;
        }
        if (!empty($row->expires_at) && $row->expires_at < date('Y-m-d')) {
            return null;
        }
        return $row;
    }

    public static function markUsed($id, $invoiceId = null)
    {
        $update = ['status' => 'used', 'used_at' => date('Y-m-d H:i:s')];
        if ($invoiceId !== null) {
            $update['invoice_id'] = (int) $invoiceId;
        }
        return (bool) WHMCS\Database\Capsule::table(self::TABLE)
            ->where('id', (int) $id)->where('status', 'active')
            ->update($update);
    }

    public static function publicUrl($row)
    {
        $systemUrl = self::systemUrl();
        if ($row->type === 'signup') {
            return $systemUrl . '/cadastro?lk=' . $row->token;
        }
        return $systemUrl . '/link/' . $row->token;
    }

    public static function systemUrl()
    {
        try {
            $url = (string) WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value');
            if ($url !== '') {
                return rtrim($url, '/');
            }
        } catch (Throwable $e) {
        }
        if (!empty($GLOBALS['CONFIG']['SystemURL'])) {
            return rtrim($GLOBALS['CONFIG']['SystemURL'], '/');
        }
        return '';
    }

    public static function resolveClient($input)
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        try {
            if (ctype_digit($input)) {
                return WHMCS\Database\Capsule::table('tblclients')->where('id', (int) $input)->first();
            }
            if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
                return WHMCS\Database\Capsule::table('tblclients')->where('email', $input)->first();
            }
        } catch (Throwable $e) {
        }
        return null;
    }

    public static function parseAmount($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (strpos($raw, ',') !== false) {
            $raw = str_replace(',', '.', $raw);
        }
        if (!is_numeric($raw)) {
            return null;
        }
        $value = round((float) $raw, 2);
        if ($value <= 0 || $value > 999999.99) {
            return null;
        }
        return $value;
    }

    public static function formatMoney($value)
    {
        return number_format((float) $value, 2, ',', '.');
    }

    public static function nextDueDateForDay($day, $from = null)
    {
        $day = max(1, min(28, (int) $day));
        $from = $from ?: date('Y-m-d');
        $base = new DateTime($from);
        $year = (int) $base->format('Y');
        $month = (int) $base->format('m');
        $candidate = sprintf('%04d-%02d-%02d', $year, $month, $day);
        if ($candidate <= $from) {
            $candidate = date('Y-m-d', strtotime($candidate . ' first day of next month'));
            $candidate = sprintf('%04d-%02d-%02d', (int) date('Y', strtotime($candidate)), (int) date('m', strtotime($candidate)), $day);
        }
        return $candidate;
    }

    public static function defaultPaymentMethod()
    {
        try {
            $enabled = WHMCS\Database\Capsule::table('tblpaymentgateways')
                ->where('gateway', 'efipixmpay')->where('setting', 'enabled')->value('value');
            if ($enabled === 'on') {
                return 'efipixmpay';
            }
        } catch (Throwable $e) {
        }
        return '';
    }

    public static function createInvoice($clientId, $amount, $dueDate, $description, $notes = '')
    {
        $params = [
            'userid' => (int) $clientId,
            'date' => date('Y-m-d'),
            'duedate' => $dueDate,
            'paymentmethod' => self::defaultPaymentMethod(),
            'sendinvoice' => '1',
            'autoapplycredit' => '0',
            'itemdescription1' => mb_substr($description, 0, 250),
            'itemamount1' => number_format((float) $amount, 2, '.', ''),
            'itemtaxed1' => '0',
            'notes' => mb_substr($notes, 0, 500),
        ];
        $response = localAPI('AddInvoice', $params, '');
        if (is_array($response) && ($response['result'] ?? '') === 'success' && !empty($response['invoiceid'])) {
            return [(int) $response['invoiceid'], ''];
        }
        $error = is_array($response) ? (string) ($response['message'] ?? 'erro desconhecido') : 'resposta inválida da API';
        return [0, $error];
    }

    public static function invoiceCreatedTemplateActive()
    {
        if (!class_exists('notificazapCore')) {
            return false;
        }
        try {
            $core = new notificazapCore();
            $template = $core->NOTIFICAZAP_Pegar_Status_Mensagem_Template('createdinvoice');
            return is_array($template) && ($template['status_mensagem'] ?? '') === 'active';
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function sanitizeText($str, $max = 250)
    {
        $str = strip_tags((string) $str);
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $str);
        return mb_substr(trim($str), 0, $max);
    }
}
