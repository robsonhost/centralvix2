<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixValidator
{
    private $options = [];

    private static $brazilUfCodes = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG',
        'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    private static $brazilStates = [
        'acre' => 'AC', 'alagoas' => 'AL', 'amapa' => 'AP', 'amazonas' => 'AM',
        'bahia' => 'BA', 'ceara' => 'CE', 'distrito federal' => 'DF', 'espirito santo' => 'ES',
        'goias' => 'GO', 'maranhao' => 'MA', 'mato grosso' => 'MT', 'mato grosso do sul' => 'MS',
        'minas gerais' => 'MG', 'para' => 'PA', 'paraiba' => 'PB', 'parana' => 'PR',
        'pernambuco' => 'PE', 'piaui' => 'PI', 'rio de janeiro' => 'RJ', 'rio grande do norte' => 'RN',
        'rio grande do sul' => 'RS', 'rondonia' => 'RO', 'roraima' => 'RR', 'santa catarina' => 'SC',
        'sao paulo' => 'SP', 'sergipe' => 'SE', 'tocantins' => 'TO',
    ];

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'validate_email' => true,
            'trim_spaces' => true,
            'remove_invalid_chars' => true,
            'normalize_phone' => true,
            'normalize_state' => true,
            'default_country' => 'BR',
        ], $options);
    }

    public function sanitizeRow(array $row)
    {
        $clean = [];
        foreach ($row as $key => $value) {
            $clean[$key] = $this->sanitizeValue($value);
        }
        return $clean;
    }

    public function sanitizeValue($value)
    {
        $value = (string) $value;

        if ($this->options['trim_spaces']) {
            $value = trim($value);
            $value = preg_replace('/\s+/', ' ', $value);
        }

        if ($this->options['remove_invalid_chars']) {
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        }

        return $value;
    }

    public function validateEmail($email)
    {
        if (!$this->options['validate_email']) {
            return $email !== '';
        }
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function normalizePhone($phone)
    {
        if (!$this->options['normalize_phone']) {
            return $phone;
        }
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 13 && substr($digits, 0, 2) === '55') {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 11) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
        }
        if (strlen($digits) === 10) {
            return '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
        }
        if (strlen($digits) >= 10 && strlen($digits) <= 11) {
            return $digits;
        }
        return '';
    }

    public function isValidPhone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 13 && substr($digits, 0, 2) === '55') {
            $digits = substr($digits, 2);
        }
        return strlen($digits) >= 10 && strlen($digits) <= 11;
    }

    public function isValidBrazilianUf($state)
    {
        return in_array(strtoupper(trim((string) $state)), self::$brazilUfCodes, true);
    }

    public function normalizeState($state)
    {
        if (!$this->options['normalize_state']) {
            return $state;
        }
        $state = trim($state);
        if (strlen($state) === 2) {
            return strtoupper($state);
        }
        $key = $this->normalizeString($state);
        if (isset(self::$brazilStates[$key])) {
            return self::$brazilStates[$key];
        }
        $upper = strtoupper($state);
        if (in_array($upper, self::$brazilUfCodes, true)) {
            return $upper;
        }
        return $state;
    }

    public function normalizeCountry($country)
    {
        $country = trim($country);
        if ($country === '' && !empty($this->options['default_country'])) {
            return $this->options['default_country'];
        }
        if (strlen($country) === 2) {
            return strtoupper($country);
        }
        $map = [
            'brasil' => 'BR', 'brazil' => 'BR',
            'portugal' => 'PT', 'estados unidos' => 'US',
        ];
        $key = $this->normalizeString($country);
        return isset($map[$key]) ? $map[$key] : $country;
    }

    public function normalizeCpfCnpj($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    public function isValidCpfCnpj($value)
    {
        $digits = $this->normalizeCpfCnpj($value);
        if (strlen($digits) === 11) {
            return $this->isValidCpf($digits);
        }
        if (strlen($digits) === 14) {
            return $this->isValidCnpj($digits);
        }
        return false;
    }

    public function formatCpfCnpj($value)
    {
        $digits = $this->normalizeCpfCnpj($value);
        if (strlen($digits) === 11) {
            return substr($digits, 0, 3) . '.' . substr($digits, 3, 3) . '.'
                . substr($digits, 6, 3) . '-' . substr($digits, 9, 2);
        }
        if (strlen($digits) === 14) {
            return substr($digits, 0, 2) . '.' . substr($digits, 2, 3) . '.'
                . substr($digits, 5, 3) . '/' . substr($digits, 8, 4) . '-'
                . substr($digits, 12, 2);
        }
        return $digits;
    }

    public function isValidCpf($cpf)
    {
        $cpf = $this->normalizeCpfCnpj($cpf);
        if (strlen($cpf) !== 11) {
            return false;
        }
        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }
        return true;
    }

    public function isValidCnpj($cnpj)
    {
        $cnpj = $this->normalizeCpfCnpj($cnpj);
        if (strlen($cnpj) !== 14) {
            return false;
        }
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($t = 0; $t < 2; $t++) {
            $sum = 0;
            $weights = $t === 0 ? $weights1 : $weights2;
            for ($i = 0; $i < 12 + $t; $i++) {
                $sum += (int) $cnpj[$i] * $weights[$i];
            }
            $digit = $sum % 11;
            $digit = $digit < 2 ? 0 : 11 - $digit;
            if ((int) $cnpj[12 + $t] !== $digit) {
                return false;
            }
        }
        return true;
    }

    public function generateValidCpfFromSeed($seed)
    {
        $hash = hash('sha256', (string) $seed);
        $digits = preg_replace('/\D/', '', $hash);
        while (strlen($digits) < 9) {
            $digits .= $digits;
        }
        $base = substr($digits, 0, 9);
        if (preg_match('/^(\d)\1{8}$/', $base)) {
            $base = '739123456';
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $base[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            $base .= (string) $digit;
        }

        return $this->isValidCpf($base) ? $base : '';
    }

    public function generateValidCnpjFromSeed($seed)
    {
        $hash = hash('sha256', (string) $seed);
        $digits = preg_replace('/\D/', '', $hash);
        while (strlen($digits) < 12) {
            $digits .= $digits;
        }
        $base = substr($digits, 0, 12);
        if (preg_match('/^(\d)\1{11}$/', $base)) {
            $base = '739123456789';
        }

        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($t = 0; $t < 2; $t++) {
            $sum = 0;
            $weights = $t === 0 ? $weights1 : $weights2;
            for ($i = 0; $i < 12 + $t; $i++) {
                $sum += (int) $base[$i] * $weights[$i];
            }
            $digit = $sum % 11;
            $digit = $digit < 2 ? 0 : 11 - $digit;
            $base .= (string) $digit;
        }

        return $this->isValidCnpj($base) ? $base : '';
    }

    private function normalizeString($str)
    {
        $str = mb_strtolower($str, 'UTF-8');
        $str = preg_replace('/[áàâãä]/u', 'a', $str);
        $str = preg_replace('/[éèêë]/u', 'e', $str);
        $str = preg_replace('/[íìîï]/u', 'i', $str);
        $str = preg_replace('/[óòôõö]/u', 'o', $str);
        $str = preg_replace('/[úùûü]/u', 'u', $str);
        $str = preg_replace('/[ç]/u', 'c', $str);
        return trim($str);
    }
}
