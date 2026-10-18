<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixImportadorHelper
{
    const MODULE_NAME = 'centralvix_importador';
    const TOKEN_NAME = 'CentralVixImportador';
    const BATCH_SIZE = 25;
    const MAX_FILE_SIZE = 10485760; // 10 MB
    const ALLOWED_EXTENSIONS = ['csv', 'xlsx', 'xls'];
    const ALLOWED_MIMES = [
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/octet-stream',
        'application/zip',
    ];

    public static function modulePath()
    {
        return dirname(__DIR__);
    }

    public static function storagePath()
    {
        return self::modulePath() . DIRECTORY_SEPARATOR . 'storage';
    }

    public static function tempPath()
    {
        return self::storagePath() . DIRECTORY_SEPARATOR . 'temp';
    }

    public static function ensureStorageDirs()
    {
        $ok = true;
        $dirs = [self::storagePath(), self::tempPath()];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $ok = false;
                    continue;
                }
            }

            if (!is_writable($dir)) {
                @chmod($dir, 0775);
            }

            self::protectStorageDir($dir);
        }

        return $ok && is_dir(self::tempPath()) && is_writable(self::tempPath());
    }

    public static function getWritableTempDirectories()
    {
        $dirs = [self::tempPath()];

        if (defined('ROOTDIR')) {
            $dirs[] = ROOTDIR . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR . 'cvix_importador';
        }

        $dirs[] = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'centralvix_importador';

        $unique = [];
        foreach ($dirs as $dir) {
            $dir = rtrim($dir, DIRECTORY_SEPARATOR);
            if ($dir !== '' && !in_array($dir, $unique, true)) {
                $unique[] = $dir;
            }
        }

        return $unique;
    }

    public static function saveUploadedFile($tmpPath, $storedName)
    {
        if (!is_uploaded_file($tmpPath) && !file_exists($tmpPath)) {
            return [
                'success' => false,
                'message' => 'Arquivo temporário de upload não encontrado.',
            ];
        }

        $errors = [];

        foreach (self::getWritableTempDirectories() as $dir) {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                    $errors[] = $dir . ' (não foi possível criar)';
                    continue;
                }
            }

            if (!is_writable($dir)) {
                @chmod($dir, 0775);
            }

            if (!is_writable($dir)) {
                $errors[] = $dir . ' (sem permissão de escrita)';
                continue;
            }

            self::protectStorageDir($dir);

            $destPath = $dir . DIRECTORY_SEPARATOR . $storedName;

            if (is_uploaded_file($tmpPath) && @move_uploaded_file($tmpPath, $destPath)) {
                @chmod($destPath, 0644);
                return ['success' => true, 'path' => $destPath];
            }

            if (@copy($tmpPath, $destPath)) {
                if (is_uploaded_file($tmpPath)) {
                    @unlink($tmpPath);
                }
                @chmod($destPath, 0644);
                return ['success' => true, 'path' => $destPath];
            }

            $errors[] = $dir . ' (falha ao gravar arquivo)';
        }

        $hint = 'Ajuste permissões: chmod -R 775 ' . self::storagePath();
        $detail = !empty($errors) ? implode('; ', $errors) : 'nenhum diretório gravável encontrado';

        return [
            'success' => false,
            'message' => 'Falha ao salvar arquivo temporário. ' . $hint . ' Detalhes: ' . $detail,
        ];
    }

    private static function protectStorageDir($dir)
    {
        $indexFile = $dir . DIRECTORY_SEPARATOR . 'index.php';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, "<?php\n// Silence is golden.\n");
        }

        $htaccessFile = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
        }
    }

    public static function isStorageWritable()
    {
        self::ensureStorageDirs();
        foreach (self::getWritableTempDirectories() as $dir) {
            if (is_dir($dir) && is_writable($dir)) {
                return true;
            }
        }
        return false;
    }

    public static function autoloadLib()
    {
        $libPath = self::modulePath() . DIRECTORY_SEPARATOR . 'lib';
        $files = [
            'ModuleHelper.php',
            'SpreadsheetReader.php',
            'Validator.php',
            'ClientMapper.php',
            'ClientService.php',
            'ImportLogger.php',
            'Importer.php',
        ];
        foreach ($files as $file) {
            if ($file === 'ModuleHelper.php') {
                continue;
            }
            $full = $libPath . DIRECTORY_SEPARATOR . $file;
            if (file_exists($full)) {
                require_once $full;
            }
        }
    }

    /**
     * Carrega PhpSpreadsheet sob demanda (somente para Excel).
     */
    public static function loadSpreadsheetLibrary()
    {
        if (class_exists('\PhpOffice\PhpSpreadsheet\IOFactory', false)) {
            return true;
        }

        if (!self::isSpreadsheetLibraryPresent()) {
            return false;
        }

        if (!self::isVendorPlatformCompatible()) {
            return false;
        }

        $vendorAutoload = self::modulePath() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        self::disableComposerPlatformCheck();

        ob_start();
        try {
            require_once $vendorAutoload;
            $loaded = class_exists('\PhpOffice\PhpSpreadsheet\IOFactory');
        } catch (Throwable $e) {
            $loaded = false;
        }
        ob_end_clean();

        return $loaded;
    }

    public static function isSpreadsheetLibraryPresent()
    {
        return is_file(self::modulePath() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');
    }

    public static function isVendorPlatformCompatible()
    {
        if (!self::isSpreadsheetLibraryPresent()) {
            return false;
        }

        if (PHP_VERSION_ID >= 80300) {
            return true;
        }

        $platformFile = self::modulePath() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'platform_check.php';
        if (!is_file($platformFile)) {
            return true;
        }

        $content = @file_get_contents($platformFile);
        if ($content === false) {
            return true;
        }

        if (preg_match('/PHP_VERSION_ID\s*<\s*80300/i', $content) || preg_match('/>=?\s*["\']?8\.3/i', $content)) {
            return false;
        }

        return true;
    }

    private static function disableComposerPlatformCheck()
    {
        @putenv('COMPOSER_DISABLE_PLATFORM_CHECK=1');
        $_ENV['COMPOSER_DISABLE_PLATFORM_CHECK'] = '1';
        $_SERVER['COMPOSER_DISABLE_PLATFORM_CHECK'] = '1';
    }

    const COMPOSER_INSTALL_HINT = 'rm -rf vendor composer.lock && composer install --no-dev';

    public static function getComposerInstallMessage()
    {
        return 'Para arquivos Excel (.xlsx/.xls), instale PhpSpreadsheet 2.x na pasta do módulo. '
            . 'Via SSH: cd modules/addons/centralvix_importador && ' . self::COMPOSER_INSTALL_HINT
            . ' — Alternativa: exporte a planilha como CSV.';
    }

    public static function getVendorFixInstructions()
    {
        return 'A pasta vendor/ está ausente ou incompatível (PHP ' . PHP_VERSION . '). '
            . 'Execute na pasta do módulo: ' . self::COMPOSER_INSTALL_HINT
            . ' — Ou importe um arquivo CSV.';
    }

    public static function getMissingPhpExtensionsForExcel()
    {
        $missing = [];
        if (!extension_loaded('zip')) {
            $missing[] = 'zip';
        }
        if (!extension_loaded('xml') && !extension_loaded('libxml')) {
            $missing[] = 'xml';
        }
        if (!extension_loaded('mbstring')) {
            $missing[] = 'mbstring';
        }
        return $missing;
    }

    public static function getExcelReadinessMessage()
    {
        if (!self::isSpreadsheetLibraryPresent()) {
            return self::getComposerInstallMessage();
        }

        if (!self::isVendorPlatformCompatible()) {
            return self::getVendorFixInstructions();
        }

        $missing = self::getMissingPhpExtensionsForExcel();
        if (!empty($missing)) {
            return 'Extensões PHP necessárias para Excel: ' . implode(', ', $missing) . '. Ative no cPanel → Select PHP Extensions (PHP 8.2).';
        }

        if (!self::loadSpreadsheetLibrary()) {
            return 'PhpSpreadsheet não carregou. ' . self::getVendorFixInstructions();
        }

        return '';
    }

    public static function isSpreadsheetAvailable()
    {
        if (!empty(self::getMissingPhpExtensionsForExcel())) {
            return false;
        }
        if (!self::isSpreadsheetLibraryPresent() || !self::isVendorPlatformCompatible()) {
            return false;
        }
        return self::loadSpreadsheetLibrary();
    }

    public static function getAdminAjaxUrl($endpoint, array $query = [])
    {
        $url = 'addonmodules.php?module=' . self::MODULE_NAME . '&cvix_ajax=' . urlencode($endpoint);
        foreach ($query as $key => $value) {
            $url .= '&' . urlencode($key) . '=' . urlencode($value);
        }
        return $url;
    }

    public static function getModuleUrl($tab = 'dashboard')
    {
        return 'addonmodules.php?module=' . self::MODULE_NAME . '&tab=' . urlencode($tab);
    }

    public static function generateCsrfToken()
    {
        if (function_exists('generate_token')) {
            $token = generate_token('plain');
            $_SESSION[self::TOKEN_NAME] = $token;
            return $token;
        }
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    public static function validateCsrfToken($token)
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        return hash_equals((string) $_SESSION[self::TOKEN_NAME], (string) $token);
    }

    public static function requireAdmin()
    {
        if (!isset($_SESSION['adminid']) || !(int) $_SESSION['adminid']) {
            self::jsonResponse(['success' => false, 'message' => 'Acesso negado. Sessão de administrador inválida.'], 403);
        }
    }

    public static function jsonResponse(array $data, $httpCode = 200)
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function getModuleSettings()
    {
        $settings = [];
        $rows = WHMCS\Database\Capsule::table('tbladdonmodules')
            ->where('module', self::MODULE_NAME)
            ->get();
        foreach ($rows as $row) {
            $settings[$row->setting] = $row->value;
        }
        return $settings;
    }

    public static function getSetting($key, $default = '')
    {
        $settings = self::getModuleSettings();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    public static function getClientGroups()
    {
        try {
            return WHMCS\Database\Capsule::table('tblclientgroups')
                ->orderBy('groupname')
                ->get(['id', 'groupname'])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getCurrencies()
    {
        try {
            return WHMCS\Database\Capsule::table('tblcurrencies')
                ->orderBy('default', 'desc')
                ->get(['id', 'code', 'default'])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getCountries()
    {
        try {
            if (class_exists('\WHMCS\Utility\Country') && method_exists('\WHMCS\Utility\Country', 'getCountries')) {
                $raw = \WHMCS\Utility\Country::getCountries();
                $formatted = self::formatCountriesList($raw);
                if (!empty($formatted)) {
                    return $formatted;
                }
            }
        } catch (Throwable $e) {
            // fallback abaixo
        }

        if (defined('ROOTDIR')) {
            $countriesFile = ROOTDIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'countries.php';
            if (file_exists($countriesFile)) {
                $countries = [];
                include $countriesFile;
                if (!empty($countries) && is_array($countries)) {
                    $formatted = self::formatCountriesList($countries);
                    if (!empty($formatted)) {
                        return $formatted;
                    }
                }
            }

            $jsonCandidates = [
                ROOTDIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'countries.json',
                ROOTDIR . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'country' . DIRECTORY_SEPARATOR . 'dist.countries.json',
            ];
            foreach ($jsonCandidates as $jsonFile) {
                if (!file_exists($jsonFile)) {
                    continue;
                }
                $decoded = json_decode(file_get_contents($jsonFile), true);
                if (!empty($decoded)) {
                    $formatted = self::formatCountriesList($decoded);
                    if (!empty($formatted)) {
                        return $formatted;
                    }
                }
            }
        }

        return self::getDefaultCountries();
    }

    private static function formatCountriesList($data)
    {
        $result = [];

        if (!is_array($data)) {
            return $result;
        }

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $item = (array) $value;
                $code = $item['countrycode'] ?? $item['code'] ?? $item['iso'] ?? $item['iso2'] ?? '';
                $name = $item['country'] ?? $item['name'] ?? $item['label'] ?? '';
                if ($code === '' && is_string($key) && strlen($key) <= 3) {
                    $code = $key;
                }
            } else {
                $code = is_string($key) ? $key : '';
                $name = $value;
            }

            $code = strtoupper(trim((string) $code));
            $name = trim((string) $name);

            if ($code === '' || $name === '') {
                continue;
            }

            $result[] = (object) [
                'countrycode' => $code,
                'country' => $name,
            ];
        }

        usort($result, function ($a, $b) {
            return strcasecmp($a->country, $b->country);
        });

        return $result;
    }

    private static function getDefaultCountries()
    {
        $list = [
            'BR' => 'Brasil',
            'PT' => 'Portugal',
            'US' => 'United States',
            'AR' => 'Argentina',
            'BO' => 'Bolivia',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'EC' => 'Ecuador',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'UY' => 'Uruguay',
            'VE' => 'Venezuela',
            'MX' => 'Mexico',
            'ES' => 'Spain',
            'FR' => 'France',
            'DE' => 'Germany',
            'IT' => 'Italy',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
        ];

        return self::formatCountriesList($list);
    }

    public static function getLanguages()
    {
        $langs = [];
        if (function_exists('getLanguages')) {
            $langs = getLanguages();
        }
        if (empty($langs)) {
            $langs = [
                'portuguese-br' => 'Português (Brasil)',
                'portuguese-pt' => 'Português (Portugal)',
                'english' => 'English',
                'spanish' => 'Español',
            ];
        }
        return $langs;
    }

    public static function getCustomClientFields()
    {
        try {
            return WHMCS\Database\Capsule::table('tblcustomfields')
                ->where('type', 'client')
                ->orderBy('sortorder')
                ->get(['id', 'fieldname', 'fieldtype', 'fieldoptions', 'required', 'regexpr'])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function getCustomClientFieldById($fieldId)
    {
        $fieldId = (int) $fieldId;
        if ($fieldId <= 0) {
            return null;
        }

        foreach (self::getCustomClientFields() as $field) {
            if ((int) $field->id === $fieldId) {
                return $field;
            }
        }

        return null;
    }

    public static function isTaxCustomField($field, $configuredFieldId = 0)
    {
        if (!$field) {
            return false;
        }

        $fieldId = (int) ($field->id ?? 0);
        if ($configuredFieldId > 0 && $fieldId === (int) $configuredFieldId) {
            return true;
        }

        $raw = mb_strtolower(html_entity_decode((string) ($field->fieldname ?? ''), ENT_QUOTES, 'UTF-8'), 'UTF-8');
        $parts = array_map('trim', explode('|', $raw));
        $haystack = trim(implode(' ', array_filter($parts, static function ($part) {
            return $part !== '';
        })));

        $needles = [
            'cpf', 'cnpj', 'documento', 'doc fiscal', 'tax id', 'tax_id', 'taxid',
            'fiscal', 'identific', 'inscr', 'nif', 'registro', 'cadastro nacional', 'vat',
        ];
        foreach ($needles as $needle) {
            if ($haystack !== '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        $regexpr = mb_strtolower((string) ($field->regexpr ?? ''), 'UTF-8');
        if ($regexpr !== '') {
            if (strpos($regexpr, 'cpf') !== false
                || strpos($regexpr, 'cnpj') !== false
                || preg_match('/\\\\d\{11\}|\\\\d\{14\}|\\d\{11\}|\\d\{14\}/', $regexpr)) {
                return true;
            }
        }

        return false;
    }

    public static function isKnownNonTaxCustomField($field)
    {
        if (!$field) {
            return false;
        }

        $raw = mb_strtolower(html_entity_decode((string) ($field->fieldname ?? ''), ENT_QUOTES, 'UTF-8'), 'UTF-8');
        $parts = array_map('trim', explode('|', $raw));
        $haystack = trim(implode(' ', array_filter($parts)));

        $safeNeedles = [
            'conheceu', 'indic', 'refer', 'origem', 'marketing', 'google', 'facebook',
            'instagram', 'amigo', 'parceiro', 'campanha', 'como nos', 'heard', 'source',
        ];
        foreach ($safeNeedles as $needle) {
            if ($haystack !== '' && strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return strtolower((string) ($field->fieldtype ?? '')) === 'dropdown';
    }

    public static function formatTaxValueForCustomField($field, $normalizedTaxId)
    {
        $normalizedTaxId = preg_replace('/\D+/', '', (string) $normalizedTaxId);
        if ($normalizedTaxId === '') {
            return '';
        }

        $validator = new CentralVixValidator();
        if (!$validator->isValidCpfCnpj($normalizedTaxId)) {
            return '';
        }

        $regexpr = (string) ($field->regexpr ?? '');
        if ($regexpr !== '' && (strpos($regexpr, '.') !== false || strpos($regexpr, '/') !== false || strpos($regexpr, '-') !== false)) {
            return $validator->formatCpfCnpj($normalizedTaxId);
        }

        return $normalizedTaxId;
    }

    public static function detectTaxCustomFieldId()
    {
        foreach (self::getRequiredClientCustomFields() as $field) {
            if (self::isTaxCustomField($field)) {
                return (int) $field->id;
            }
        }

        foreach (self::getCustomClientFields() as $field) {
            if (self::isTaxCustomField($field)) {
                return (int) $field->id;
            }
        }

        return 0;
    }

    public static function getDashboardStats()
    {
        try {
            if (!WHMCS\Database\Capsule::schema()->hasTable('mod_centralvix_importacoes')) {
                return self::emptyDashboardStats();
            }

            $totals = WHMCS\Database\Capsule::table('mod_centralvix_importacoes')
                ->selectRaw('
                    COALESCE(SUM(total), 0) as total_records,
                    COALESCE(SUM(imported), 0) as total_imported,
                    COALESCE(SUM(skipped), 0) as total_skipped,
                    COALESCE(SUM(updated), 0) as total_updated,
                    COALESCE(SUM(errors), 0) as total_errors
                ')
                ->first();

            return [
                'total_records' => (int) ($totals->total_records ?? 0),
                'total_imported' => (int) ($totals->total_imported ?? 0),
                'total_skipped' => (int) ($totals->total_skipped ?? 0),
                'total_updated' => (int) ($totals->total_updated ?? 0),
                'total_errors' => (int) ($totals->total_errors ?? 0),
            ];
        } catch (Throwable $e) {
            return self::emptyDashboardStats();
        }
    }

    private static function emptyDashboardStats()
    {
        return [
            'total_records' => 0,
            'total_imported' => 0,
            'total_skipped' => 0,
            'total_updated' => 0,
            'total_errors' => 0,
        ];
    }

    public static function sanitizeFilename($filename)
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        return $filename;
    }

    public static function detectRealExtension($filepath, $originalName)
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        if (!is_readable($filepath)) {
            return false;
        }

        $mime = null;
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = @finfo_file($finfo, $filepath);
                finfo_close($finfo);
            }
        }

        if ($mime) {
            if (in_array($mime, self::ALLOWED_MIMES, true)) {
                return $ext;
            }
            if ($ext === 'csv' && in_array($mime, ['text/plain', 'application/csv', 'text/csv'], true)) {
                return 'csv';
            }
            if (in_array($ext, ['xlsx', 'xls'], true) && in_array($mime, ['application/octet-stream', 'application/zip'], true)) {
                return $ext;
            }
            return false;
        }

        return $ext;
    }

    public static function renderTemplate($template, array $vars = [])
    {
        extract($vars);
        $templateFile = self::modulePath() . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template . '.php';
        if (!file_exists($templateFile)) {
            echo '<div class="alert alert-danger">Template não encontrado: ' . htmlspecialchars($template) . '</div>';
            return;
        }
        include $templateFile;
    }

    public static function getAdminName($adminId)
    {
        $admin = WHMCS\Database\Capsule::table('tbladmins')
            ->where('id', (int) $adminId)
            ->first(['firstname', 'lastname', 'username']);
        if (!$admin) {
            return 'Desconhecido';
        }
        $name = trim($admin->firstname . ' ' . $admin->lastname);
        return $name ?: $admin->username;
    }

    /**
     * Interpreta mapeamento enviado via POST (JSON, base64 ou fallback do banco).
     */
    public static function parseMappingFromRequest($import = null)
    {
        if (!empty($_POST['mapping_b64']) && is_string($_POST['mapping_b64'])) {
            $raw = base64_decode($_POST['mapping_b64'], true);
            if ($raw !== false) {
                $decoded = self::decodeMappingJson($raw);
                if ($decoded !== null) {
                    return $decoded;
                }
            }
        }

        $mappingRaw = $_POST['mapping'] ?? null;
        if (is_array($mappingRaw)) {
            return self::sanitizeMappingArray($mappingRaw);
        }

        if (is_string($mappingRaw) && trim($mappingRaw) !== '') {
            $decoded = self::decodeMappingJson($mappingRaw);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        if ($import && !empty($import->mapping)) {
            $stored = self::decodeMappingJson($import->mapping);
            if ($stored !== null && !empty($stored)) {
                return $stored;
            }
        }

        return null;
    }

    private static function decodeMappingJson($raw)
    {
        if (!is_string($raw)) {
            return null;
        }

        $raw = trim($raw);
        if ($raw === '' || $raw === 'undefined' || $raw === 'null') {
            return null;
        }

        $flags = defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0;
        $decoded = json_decode($raw, true, 512, $flags);
        if (is_array($decoded)) {
            return self::sanitizeMappingArray($decoded);
        }

        $stripped = stripslashes($raw);
        if ($stripped !== $raw) {
            $decoded = json_decode($stripped, true, 512, $flags);
            if (is_array($decoded)) {
                return self::sanitizeMappingArray($decoded);
            }
        }

        return null;
    }

    public static function sanitizeMappingArray(array $mapping)
    {
        $allowed = array_keys(CentralVixClientMapper::getWhmcsFields());
        $clean = [];

        foreach ($mapping as $column => $field) {
            $column = trim((string) $column);
            $field = trim((string) $field);
            if ($column === '' || $field === '') {
                continue;
            }
            if ($field === 'email' || in_array($field, $allowed, true) || strpos($field, 'custom_') === 0) {
                $clean[$column] = $field;
            }
        }

        return $clean;
    }

    public static function getRequiredClientCustomFields()
    {
        try {
            return WHMCS\Database\Capsule::table('tblcustomfields')
                ->where('type', 'client')
                ->where('required', 'on')
                ->orderBy('sortorder')
                ->get(['id', 'fieldname', 'fieldtype', 'fieldoptions', 'required', 'regexpr'])
                ->all();
        } catch (Throwable $e) {
            return [];
        }
    }

    public static function mappingHasEmail(array $mapping)
    {
        foreach ($mapping as $field) {
            if ($field === 'email') {
                return true;
            }
        }
        return false;
    }

    public static function sanitizeImportText($value, $max = 200)
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $max);
    }
}
