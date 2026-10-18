<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

class CentralVixClientService
{
    private $validator;
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'skip_existing_email' => true,
            'update_existing' => false,
            'check_tax_id' => false,
            'tax_id_custom_field' => 0,
            'generate_password' => true,
            'validate_email' => true,
            'trim_spaces' => true,
            'remove_invalid_chars' => true,
            'normalize_phone' => true,
            'normalize_state' => true,
            'default_country' => 'BR',
            'default_currency' => '',
            'default_groupid' => '',
            'default_language' => 'portuguese-br',
            'fill_required_custom_fields' => true,
            'default_custom_field_text' => 'Importação em massa',
            'default_phone' => '(11) 99999-9999',
            'default_address1' => 'Não informado',
            'default_city' => 'Não informado',
            'default_state' => 'SP',
            'sync_tax_id_custom_field' => true,
            'allow_missing_tax_id' => false,
            'generate_missing_tax_id' => false,
        ], $options);

        $this->validator = new CentralVixValidator([
            'validate_email' => $this->options['validate_email'],
            'trim_spaces' => $this->options['trim_spaces'],
            'remove_invalid_chars' => $this->options['remove_invalid_chars'],
            'normalize_phone' => $this->options['normalize_phone'],
            'normalize_state' => $this->options['normalize_state'],
            'default_country' => $this->options['default_country'],
        ]);
    }

    public function processClient(array $clientData, $rowNumber)
    {
        $clientData = $this->validator->sanitizeRow($clientData);
        $clientData = $this->applyImportDefaults($clientData);
        $clientData = $this->resolveTaxDocumentStrategy($clientData, $rowNumber);

        $email = trim($clientData['email'] ?? '');
        if ($email === '') {
            return $this->result('error', 0, 'E-mail obrigatório não informado.', $clientData, $rowNumber);
        }

        if (!$this->validator->validateEmail($email)) {
            return $this->result('error', 0, 'E-mail inválido.', $clientData, $rowNumber);
        }

        $existing = $this->findExistingClient($email, $clientData);

        if ($existing && $this->options['skip_existing_email'] && !$this->options['update_existing']) {
            return $this->result('skipped', (int) $existing->id, 'Cliente já cadastrado com este e-mail.', $clientData, $rowNumber);
        }

        if ($existing && $this->options['update_existing']) {
            $taxError = $this->validateRequiredTaxFields($clientData);
            if ($taxError !== null) {
                return $this->result('error', (int) $existing->id, $taxError, $clientData, $rowNumber);
            }
            return $this->updateClient((int) $existing->id, $clientData, $rowNumber);
        }

        if ($existing && !$this->options['update_existing']) {
            return $this->result('skipped', (int) $existing->id, 'Cliente já cadastrado com este e-mail.', $clientData, $rowNumber);
        }

        $taxError = $this->validateRequiredTaxFields($clientData);
        if ($taxError !== null) {
            return $this->result('error', 0, $taxError, $clientData, $rowNumber);
        }

        return $this->createClient($clientData, $rowNumber);
    }

    private function validateRequiredTaxFields(array $clientData)
    {
        if (!empty($this->options['allow_missing_tax_id'])) {
            return null;
        }

        foreach (CentralVixImportadorHelper::getRequiredClientCustomFields() as $field) {
            if (!CentralVixImportadorHelper::isTaxCustomField($field, (int) ($this->options['tax_id_custom_field'] ?? 0))) {
                continue;
            }
            $fid = (int) $field->id;
            $val = $clientData['customfields'][$fid] ?? '';
            if ($val === '' && empty($clientData['tax_id'])) {
                return 'CPF/CNPJ ausente ou inválido. Mapeie a coluna CPF/CNPJ na etapa 3 ou inclua na planilha.';
            }
        }
        return null;
    }

    /**
     * Define estratégia fiscal: mantém CPF real da planilha, gera provisório ou deixa vazio (insert direto).
     */
    private function resolveTaxDocumentStrategy(array $clientData, $rowNumber)
    {
        if ($this->hasValidTaxDocument($clientData)) {
            return $this->syncTaxToCustomFields($clientData);
        }

        if (!empty($this->options['allow_missing_tax_id'])) {
            return $this->stripTaxFieldsFromClientData($clientData);
        }

        if (!empty($this->options['generate_missing_tax_id'])) {
            return $this->ensureTaxDocument($clientData, $rowNumber);
        }

        return $clientData;
    }

    private function stripTaxFieldsFromClientData(array $clientData)
    {
        unset($clientData['tax_id']);

        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return $clientData;
        }

        $configured = (int) ($this->options['tax_id_custom_field'] ?? 0);
        foreach ($clientData['customfields'] as $fieldId => $value) {
            $field = CentralVixImportadorHelper::getCustomClientFieldById((int) $fieldId);
            if (CentralVixImportadorHelper::isTaxCustomField($field, $configured)) {
                unset($clientData['customfields'][(int) $fieldId]);
            }
        }

        return $clientData;
    }

    private function shouldImportWithoutTaxDocument(array $clientData)
    {
        if (empty($this->options['allow_missing_tax_id'])) {
            return false;
        }

        return !$this->hasValidTaxDocument($clientData);
    }

    private function applyImportDefaults(array $clientData)
    {
        $clientData['country'] = $this->validator->normalizeCountry($clientData['country'] ?? '');

        if (!empty($clientData['state'])) {
            $clientData['state'] = $this->validator->normalizeState($clientData['state']);
        }

        if ($clientData['country'] === 'BR') {
            if (empty($clientData['state']) || !$this->validator->isValidBrazilianUf($clientData['state'])) {
                $clientData['state'] = $this->options['default_state'] ?: 'SP';
            }
        } elseif (empty($clientData['state']) && !empty($this->options['default_state'])) {
            $clientData['state'] = $this->options['default_state'];
        }

        if (!empty($clientData['phonenumber'])) {
            $clientData['phonenumber'] = $this->validator->normalizePhone($clientData['phonenumber']);
        }
        if (empty($clientData['phonenumber']) || !$this->validator->isValidPhone($clientData['phonenumber'])) {
            $clientData['phonenumber'] = $this->options['default_phone'] ?: '(11) 99999-9999';
        }

        if (empty($clientData['address1'])) {
            $clientData['address1'] = $this->options['default_address1'] ?: 'Não informado';
        }
        if (empty($clientData['city'])) {
            $clientData['city'] = $this->options['default_city'] ?: 'Não informado';
        }
        if (empty($clientData['postcode'])) {
            $clientData['postcode'] = '00000-000';
        }

        if (empty($clientData['currency']) && !empty($this->options['default_currency'])) {
            $clientData['currency'] = $this->options['default_currency'];
        }
        if (empty($clientData['groupid']) && $this->options['default_groupid'] !== '') {
            $clientData['groupid'] = $this->options['default_groupid'];
        }
        if (empty($clientData['language']) && !empty($this->options['default_language'])) {
            $clientData['language'] = $this->options['default_language'];
        }

        if (!isset($clientData['customfields']) || !is_array($clientData['customfields'])) {
            $clientData['customfields'] = [];
        }

        $taxId = trim($clientData['tax_id'] ?? '');
        if ($taxId !== '') {
            $normalizedTax = $this->validator->normalizeCpfCnpj($taxId);
            if ($this->validator->isValidCpfCnpj($normalizedTax)) {
                $formattedTax = $this->validator->formatCpfCnpj($normalizedTax);
                $clientData['tax_id'] = $normalizedTax;
                $fieldId = (int) ($this->options['tax_id_custom_field'] ?? 0);
                if ($fieldId > 0 && !empty($this->options['sync_tax_id_custom_field'])) {
                    if (empty($clientData['customfields'][$fieldId])) {
                        $clientData['customfields'][$fieldId] = $formattedTax;
                    }
                }
            } else {
                unset($clientData['tax_id']);
            }
        }

        if (!empty($this->options['fill_required_custom_fields'])) {
            $clientData = $this->fillRequiredCustomFields($clientData);
        }

        $clientData = $this->sanitizeTaxCustomFields($clientData);

        if (trim($clientData['firstname'] ?? '') === '' && trim($clientData['lastname'] ?? '') === '') {
            $email = $clientData['email'] ?? '';
            $clientData['firstname'] = $email !== '' ? strstr($email, '@', true) : 'Cliente';
            $clientData['lastname'] = 'Importado';
        } elseif (trim($clientData['firstname'] ?? '') === '') {
            $clientData['firstname'] = $clientData['lastname'];
        } elseif (trim($clientData['lastname'] ?? '') === '') {
            $parts = preg_split('/\s+/', trim($clientData['firstname']), 2);
            if (count($parts) === 2) {
                $clientData['firstname'] = $parts[0];
                $clientData['lastname'] = $parts[1];
            } else {
                $clientData['lastname'] = 'Importado';
            }
        }

        return $clientData;
    }

    private function ensureTaxDocument(array $clientData, $rowNumber)
    {
        if ($this->hasValidTaxDocument($clientData)) {
            return $this->syncTaxToCustomFields($clientData);
        }

        if (empty($this->options['generate_missing_tax_id'])) {
            return $clientData;
        }

        $preferCnpj = $this->shouldPreferGeneratedCnpj($clientData);
        $seed = mb_strtolower(trim($clientData['email'] ?? ''), 'UTF-8') . '|row:' . (int) $rowNumber;
        $taxId = $this->resolveUniqueTaxId($seed, $preferCnpj);
        if ($taxId === '') {
            return $clientData;
        }

        $clientData['tax_id'] = $taxId;
        return $this->syncTaxToCustomFields($clientData);
    }

    private function hasValidTaxDocument(array $clientData)
    {
        if (!empty($clientData['tax_id']) && $this->validator->isValidCpfCnpj($clientData['tax_id'])) {
            return true;
        }

        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return false;
        }

        foreach ($this->getTaxCustomFieldIds() as $fieldId) {
            $value = trim((string) ($clientData['customfields'][$fieldId] ?? ''));
            if ($value !== '' && $this->validator->isValidCpfCnpj($value)) {
                return true;
            }
        }

        return false;
    }

    private function resolveUniqueTaxId($seed, $preferCnpj = false)
    {
        for ($attempt = 0; $attempt < 25; $attempt++) {
            $candidateSeed = $seed . '|try:' . $attempt;
            $taxId = $preferCnpj
                ? $this->validator->generateValidCnpjFromSeed($candidateSeed)
                : $this->validator->generateValidCpfFromSeed($candidateSeed);

            if ($taxId === '' || $this->taxIdExists($taxId)) {
                continue;
            }

            return $taxId;
        }

        return '';
    }

    private function taxIdExists($taxId)
    {
        $normalized = $this->validator->normalizeCpfCnpj($taxId);
        if ($normalized === '') {
            return false;
        }

        try {
            if (Capsule::schema()->hasColumn('tblclients', 'tax_id')
                && Capsule::table('tblclients')->where('tax_id', $normalized)->exists()) {
                return true;
            }
        } catch (Throwable $e) {
            // ignore schema/query issues
        }

        $formatted = $this->validator->formatCpfCnpj($normalized);
        foreach ($this->getTaxCustomFieldIds() as $fieldId) {
            try {
                $exists = Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', (int) $fieldId)
                    ->where(function ($query) use ($normalized, $formatted) {
                        $query->where('value', $normalized)->orWhere('value', $formatted);
                    })
                    ->exists();
                if ($exists) {
                    return true;
                }
            } catch (Throwable $e) {
                // ignore
            }
        }

        return false;
    }

    private function shouldPreferGeneratedCnpj(array $clientData)
    {
        if (trim($clientData['companyname'] ?? '') === '') {
            return false;
        }

        $configured = (int) ($this->options['tax_id_custom_field'] ?? 0);
        foreach (CentralVixImportadorHelper::getCustomClientFields() as $field) {
            if (!CentralVixImportadorHelper::isTaxCustomField($field, $configured)) {
                continue;
            }
            $raw = mb_strtolower(html_entity_decode((string) ($field->fieldname ?? ''), ENT_QUOTES, 'UTF-8'), 'UTF-8');
            $haystack = trim(implode(' ', array_map('trim', explode('|', $raw))));
            if (strpos($haystack, 'cnpj') !== false && strpos($haystack, 'cpf') === false) {
                return true;
            }
        }

        return false;
    }

    private function syncTaxToCustomFields(array $clientData)
    {
        if (empty($clientData['tax_id']) || !$this->validator->isValidCpfCnpj($clientData['tax_id'])) {
            return $clientData;
        }

        if (!isset($clientData['customfields']) || !is_array($clientData['customfields'])) {
            $clientData['customfields'] = [];
        }

        $normalized = $this->validator->normalizeCpfCnpj($clientData['tax_id']);
        $clientData['tax_id'] = $normalized;
        $configuredField = (int) ($this->options['tax_id_custom_field'] ?? 0);

        foreach (CentralVixImportadorHelper::getCustomClientFields() as $field) {
            if (!CentralVixImportadorHelper::isTaxCustomField($field, $configuredField)) {
                continue;
            }
            $value = CentralVixImportadorHelper::formatTaxValueForCustomField($field, $normalized);
            if ($value !== '') {
                $clientData['customfields'][(int) $field->id] = $value;
            }
        }

        return $clientData;
    }

    private function purgeUnsafeCustomFieldValues(array $clientData)
    {
        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return $clientData;
        }

        $configuredField = (int) ($this->options['tax_id_custom_field'] ?? 0);
        foreach ($clientData['customfields'] as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);
            $field = CentralVixImportadorHelper::getCustomClientFieldById($fieldId);
            $isTaxField = CentralVixImportadorHelper::isTaxCustomField($field, $configuredField);

            if ($isTaxField) {
                if ($value === '' || $this->isPlaceholderTaxValue($value) || !$this->validator->isValidCpfCnpj($value)) {
                    unset($clientData['customfields'][$fieldId]);
                }
                continue;
            }

            if ($this->isPlaceholderTaxValue($value) && !CentralVixImportadorHelper::isKnownNonTaxCustomField($field)) {
                unset($clientData['customfields'][$fieldId]);
            }
        }

        return $clientData;
    }

    private function sanitizeTaxCustomFields(array $clientData)
    {
        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return $clientData;
        }

        $taxFieldIds = $this->getTaxCustomFieldIds();

        foreach ($clientData['customfields'] as $fid => $value) {
            $fid = (int) $fid;
            if (!in_array($fid, $taxFieldIds, true)) {
                continue;
            }
            $normalized = $this->validator->normalizeCpfCnpj($value);
            if ($this->validator->isValidCpfCnpj($normalized)) {
                $clientData['customfields'][$fid] = $this->validator->formatCpfCnpj($normalized);
                if (empty($clientData['tax_id'])) {
                    $clientData['tax_id'] = $normalized;
                }
            } else {
                unset($clientData['customfields'][$fid]);
            }
        }

        return $clientData;
    }

    private function getTaxCustomFieldIds()
    {
        $ids = [];
        $configured = (int) ($this->options['tax_id_custom_field'] ?? 0);
        if ($configured > 0) {
            $ids[] = $configured;
        } else {
            $detected = CentralVixImportadorHelper::detectTaxCustomFieldId();
            if ($detected > 0) {
                $ids[] = $detected;
            }
        }

        foreach (CentralVixImportadorHelper::getRequiredClientCustomFields() as $field) {
            if (CentralVixImportadorHelper::isTaxCustomField($field, $configured)) {
                $ids[] = (int) $field->id;
            }
        }
        foreach (CentralVixImportadorHelper::getCustomClientFields() as $field) {
            if (CentralVixImportadorHelper::isTaxCustomField($field, $configured)) {
                $ids[] = (int) $field->id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function fillRequiredCustomFields(array $clientData)
    {
        $defaultText = $this->options['default_custom_field_text'] ?: 'Importação em massa';
        $requiredFields = CentralVixImportadorHelper::getRequiredClientCustomFields();

        foreach ($requiredFields as $field) {
            $fid = (int) $field->id;

            if (CentralVixImportadorHelper::isTaxCustomField($field, (int) ($this->options['tax_id_custom_field'] ?? 0))) {
                if (empty($clientData['customfields'][$fid]) && !empty($clientData['tax_id'])
                    && $this->validator->isValidCpfCnpj($clientData['tax_id'])) {
                    $clientData['customfields'][$fid] = CentralVixImportadorHelper::formatTaxValueForCustomField(
                        $field,
                        $clientData['tax_id']
                    );
                }
                continue;
            }

            if (!empty($clientData['customfields'][$fid])) {
                continue;
            }

            if (!CentralVixImportadorHelper::isKnownNonTaxCustomField($field)
                && strtolower((string) ($field->fieldtype ?? 'text')) === 'text') {
                continue;
            }

            $value = $this->resolveDefaultCustomFieldValue($field, $defaultText);
            if ($value !== '') {
                $clientData['customfields'][$fid] = $value;
            }
        }

        return $clientData;
    }

    private function fieldLooksLikeTaxId($field)
    {
        return CentralVixImportadorHelper::isTaxCustomField(
            $field,
            (int) ($this->options['tax_id_custom_field'] ?? 0)
        );
    }

    private function resolveDefaultCustomFieldValue($field, $defaultText)
    {
        $type = strtolower($field->fieldtype ?? 'text');

        if ($type === 'dropdown') {
            $options = preg_split('/[\r\n,]+/', (string) ($field->fieldoptions ?? ''), -1, PREG_SPLIT_NO_EMPTY);
            $options = array_values(array_filter(array_map('trim', $options)));
            if (empty($options)) {
                return $defaultText;
            }
            foreach ($options as $opt) {
                $lower = mb_strtolower($opt, 'UTF-8');
                if (strpos($lower, 'import') !== false
                    || strpos($lower, 'outro') !== false
                    || strpos($lower, 'indica') !== false
                    || strpos($lower, 'google') !== false) {
                    return $opt;
                }
            }
            return $options[0];
        }

        if ($type === 'tickbox') {
            return '';
        }

        return $defaultText;
    }

    private function findExistingClient($email, array $clientData)
    {
        $client = Capsule::table('tblclients')->where('email', $email)->first();
        if ($client) {
            return $client;
        }

        if ($this->options['check_tax_id']) {
            $taxId = trim($clientData['tax_id'] ?? '');
            if ($taxId !== '') {
                $normalizedTax = $this->validator->normalizeCpfCnpj($taxId);
                $client = Capsule::table('tblclients')->where('tax_id', $normalizedTax)->first();
                if ($client) {
                    return $client;
                }
                if (!empty($this->options['tax_id_custom_field'])) {
                    $fieldId = (int) $this->options['tax_id_custom_field'];
                    $customValue = Capsule::table('tblcustomfieldsvalues')
                        ->where('fieldid', $fieldId)
                        ->where('value', $normalizedTax)
                        ->first();
                    if ($customValue) {
                        return Capsule::table('tblclients')->where('id', $customValue->relid)->first();
                    }
                }
            }
        }

        return null;
    }

    private function createClient(array $clientData, $rowNumber)
    {
        $password = trim($clientData['password2'] ?? '');
        if ($password === '') {
            if ($this->options['generate_password']) {
                $password = $this->generateSecurePassword();
            } else {
                return $this->result('error', 0, 'Senha não informada e geração automática desabilitada.', $clientData, $rowNumber);
            }
        }

        if ($this->shouldImportWithoutTaxDocument($clientData)) {
            return $this->createClientViaDatabase($clientData, $password, $rowNumber);
        }

        $clientData = $this->prepareClientDataForApi($clientData);
        $clientData = $this->purgeUnsafeCustomFieldValues($clientData);
        $clientData = $this->syncTaxToCustomFields($clientData);
        $postData = $this->buildApiData($clientData, $password);
        $response = localAPI('AddClient', $postData);

        if (isset($response['result']) && $response['result'] === 'success') {
            $clientId = (int) ($response['clientid'] ?? 0);
            return $this->result('imported', $clientId, 'Cliente importado com sucesso.', $clientData, $rowNumber);
        }

        $rawMessage = trim((string) ($response['message'] ?? ''));
        if ($this->isTaxValidationError($rawMessage) && !empty($this->options['allow_missing_tax_id'])) {
            $clientData = $this->stripTaxFieldsFromClientData($clientData);
            return $this->createClientViaDatabase($clientData, $password, $rowNumber);
        }

        $message = $this->translateWhmcsMessage($rawMessage ?: 'Erro desconhecido ao criar cliente.');
        return $this->result('error', 0, $message, $clientData, $rowNumber);
    }

    private function createClientViaDatabase(array $clientData, $password, $rowNumber)
    {
        try {
            $clientData = $this->stripTaxFieldsFromClientData($clientData);
            $clientData = $this->purgeUnsafeCustomFieldValues($clientData);

            $now = date('Y-m-d H:i:s');
            $insert = [
                'firstname' => trim($clientData['firstname'] ?? '') ?: 'Cliente',
                'lastname' => trim($clientData['lastname'] ?? '') ?: 'Importado',
                'companyname' => trim($clientData['companyname'] ?? ''),
                'email' => trim($clientData['email'] ?? ''),
                'address1' => trim($clientData['address1'] ?? '') ?: 'Não informado',
                'address2' => trim($clientData['address2'] ?? ''),
                'city' => trim($clientData['city'] ?? '') ?: 'Não informado',
                'state' => trim($clientData['state'] ?? '') ?: 'SP',
                'postcode' => trim($clientData['postcode'] ?? '') ?: '00000-000',
                'country' => trim($clientData['country'] ?? '') ?: 'BR',
                'phonenumber' => trim($clientData['phonenumber'] ?? '') ?: '(11) 99999-9999',
                'password' => $this->hashClientPassword($password),
                'status' => 'Active',
                'datecreated' => $now,
                'language' => trim($clientData['language'] ?? '') ?: 'portuguese-br',
                'currency' => $this->resolveCurrencyId($clientData['currency'] ?? ''),
                'groupid' => (int) ($clientData['groupid'] ?? 0),
            ];

            if (Capsule::schema()->hasColumn('tblclients', 'uuid')) {
                $insert['uuid'] = $this->generateClientUuid();
            }
            if (Capsule::schema()->hasColumn('tblclients', 'tax_id')) {
                $insert['tax_id'] = '';
            }
            if (Capsule::schema()->hasColumn('tblclients', 'email_verified')) {
                $insert['email_verified'] = 0;
            }

            $clientId = (int) Capsule::table('tblclients')->insertGetId($insert);
            if ($clientId <= 0) {
                return $this->result('error', 0, 'Falha ao inserir cliente no banco de dados.', $clientData, $rowNumber);
            }

            $this->insertClientCustomFields($clientId, $clientData, true);
            $this->addPendingTaxAdminNote($clientId);

            if (function_exists('run_hook')) {
                $hookData = $insert;
                unset($hookData['password']);
                run_hook('ClientAdd', array_merge($hookData, [
                    'userid' => $clientId,
                    'client_id' => $clientId,
                ]));
            }

            return $this->result(
                'imported',
                $clientId,
                'Cliente importado sem CPF/CNPJ (cadastro pendente de atualização).',
                $clientData,
                $rowNumber
            );
        } catch (Throwable $e) {
            return $this->result('error', 0, 'Erro ao importar cliente: ' . $e->getMessage(), $clientData, $rowNumber);
        }
    }

    private function insertClientCustomFields($clientId, array $clientData, $excludeTaxFields = false)
    {
        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return;
        }

        $configured = (int) ($this->options['tax_id_custom_field'] ?? 0);
        foreach ($clientData['customfields'] as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            $field = CentralVixImportadorHelper::getCustomClientFieldById($fieldId);
            if ($excludeTaxFields && CentralVixImportadorHelper::isTaxCustomField($field, $configured)) {
                continue;
            }
            if ($this->isPlaceholderTaxValue($value) && !CentralVixImportadorHelper::isKnownNonTaxCustomField($field)) {
                continue;
            }

            Capsule::table('tblcustomfieldsvalues')->insert([
                'fieldid' => $fieldId,
                'relid' => (int) $clientId,
                'value' => $value,
            ]);
        }
    }

    private function addPendingTaxAdminNote($clientId)
    {
        try {
            if (!Capsule::schema()->hasTable('tblnotes')) {
                return;
            }

            $now = date('Y-m-d H:i:s');
            $noteText = 'Importado sem CPF/CNPJ. Solicite ao cliente a atualização do cadastro.';
            $data = ['userid' => (int) $clientId];

            if (Capsule::schema()->hasColumn('tblnotes', 'note')) {
                $data['note'] = $noteText;
            } elseif (Capsule::schema()->hasColumn('tblnotes', 'description')) {
                $data['description'] = $noteText;
            }

            if (Capsule::schema()->hasColumn('tblnotes', 'adminid')) {
                $data['adminid'] = (int) ($_SESSION['adminid'] ?? 0);
            } elseif (Capsule::schema()->hasColumn('tblnotes', 'admin')) {
                $data['admin'] = (int) ($_SESSION['adminid'] ?? 0);
            }

            if (Capsule::schema()->hasColumn('tblnotes', 'created')) {
                $data['created'] = $now;
            }
            if (Capsule::schema()->hasColumn('tblnotes', 'modified')) {
                $data['modified'] = $now;
            }
            if (Capsule::schema()->hasColumn('tblnotes', 'date')) {
                $data['date'] = $now;
            }

            Capsule::table('tblnotes')->insert($data);
        } catch (Throwable $e) {
            // optional
        }
    }

    private function hashClientPassword($password)
    {
        if (class_exists('\\WHMCS\\Security\\Hash\\Password')) {
            return (new \WHMCS\Security\Hash\Password())->hash($password);
        }
        if (function_exists('encrypt')) {
            return encrypt($password);
        }

        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function resolveCurrencyId($currency)
    {
        if (is_numeric($currency) && (int) $currency > 0) {
            return (int) $currency;
        }

        $code = strtoupper(trim((string) $currency));
        if ($code !== '') {
            $row = Capsule::table('tblcurrencies')->where('code', $code)->first();
            if ($row) {
                return (int) $row->id;
            }
        }

        $default = Capsule::table('tblcurrencies')->where('default', 1)->first();
        if ($default) {
            return (int) $default->id;
        }

        $first = Capsule::table('tblcurrencies')->orderBy('id')->first();
        return $first ? (int) $first->id : 1;
    }

    private function generateClientUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function isTaxValidationError($message)
    {
        $message = mb_strtolower(trim((string) $message), 'UTF-8');
        if ($message === '') {
            return false;
        }

        return strpos($message, 'cpf') !== false
            || strpos($message, 'cnpj') !== false
            || strpos($message, 'tax') !== false
            || strpos($message, 'documento') !== false;
    }

    private function updateClient($clientId, array $clientData, $rowNumber)
    {
        $clientData = $this->prepareClientDataForApi($clientData);
        $clientData = $this->purgeUnsafeCustomFieldValues($clientData);
        $clientData = $this->syncTaxToCustomFields($clientData);
        $postData = $this->buildApiData($clientData, null, $clientId);
        unset($postData['password']);

        $response = localAPI('UpdateClient', $postData);

        if (isset($response['result']) && $response['result'] === 'success') {
            return $this->result('updated', $clientId, 'Cliente atualizado com sucesso.', $clientData, $rowNumber);
        }

        $message = $this->translateWhmcsMessage($response['message'] ?? 'Erro desconhecido ao atualizar cliente.');
        return $this->result('error', $clientId, $message, $clientData, $rowNumber);
    }

    private function prepareClientDataForApi(array $clientData)
    {
        if (!empty($clientData['tax_id'])) {
            if ($this->isPlaceholderTaxValue($clientData['tax_id'])
                || !$this->validator->isValidCpfCnpj($clientData['tax_id'])) {
                unset($clientData['tax_id']);
            } else {
                $clientData['tax_id'] = $this->validator->normalizeCpfCnpj($clientData['tax_id']);
            }
        }

        $clientData = $this->syncTaxToCustomFields($clientData);

        if (empty($clientData['customfields']) || !is_array($clientData['customfields'])) {
            return $clientData;
        }

        $configuredTaxField = (int) ($this->options['tax_id_custom_field'] ?? 0);
        foreach ($clientData['customfields'] as $fieldId => $value) {
            $fieldId = (int) $fieldId;
            $value = trim((string) $value);
            if ($value === '') {
                unset($clientData['customfields'][$fieldId]);
                continue;
            }

            $field = CentralVixImportadorHelper::getCustomClientFieldById($fieldId);
            $isTaxField = CentralVixImportadorHelper::isTaxCustomField($field, $configuredTaxField)
                || in_array($fieldId, $this->getTaxCustomFieldIds(), true);

            if (!$isTaxField) {
                continue;
            }

            if ($this->isPlaceholderTaxValue($value) || !$this->validator->isValidCpfCnpj($value)) {
                unset($clientData['customfields'][$fieldId]);
                continue;
            }

            $clientData['customfields'][$fieldId] = CentralVixImportadorHelper::formatTaxValueForCustomField($field, $value);
        }

        return $clientData;
    }

    private function isPlaceholderTaxValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return true;
        }

        $defaultText = trim((string) ($this->options['default_custom_field_text'] ?? ''));
        if ($defaultText !== '' && strcasecmp($value, $defaultText) === 0) {
            return true;
        }

        $lower = mb_strtolower($value, 'UTF-8');
        return strpos($lower, 'importa') !== false
            || strpos($lower, 'massa') !== false
            || strpos($lower, 'nao informado') !== false
            || strpos($lower, 'não informado') !== false;
    }

    private function buildApiData(array $clientData, $password = null, $clientId = null)
    {
        $data = [];

        if ($clientId) {
            $data['clientid'] = $clientId;
        }

        $fields = ['firstname', 'lastname', 'companyname', 'email', 'address1', 'address2',
            'city', 'state', 'postcode', 'country', 'phonenumber', 'currency', 'groupid', 'language'];

        foreach ($fields as $field) {
            if (isset($clientData[$field]) && $clientData[$field] !== '') {
                $data[$field] = $clientData[$field];
            }
        }

        if (!empty($clientData['tax_id']) && $this->validator->isValidCpfCnpj($clientData['tax_id'])) {
            $data['tax_id'] = $this->validator->formatCpfCnpj($clientData['tax_id']);
        }

        if ($password !== null) {
            $data['password2'] = $password;
        }

        if (!empty($clientData['customfields']) && is_array($clientData['customfields'])) {
            $customFields = [];
            $configuredTaxField = (int) ($this->options['tax_id_custom_field'] ?? 0);
            foreach ($clientData['customfields'] as $fieldId => $value) {
                $fieldId = (int) $fieldId;
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $field = CentralVixImportadorHelper::getCustomClientFieldById($fieldId);
                if (CentralVixImportadorHelper::isTaxCustomField($field, $configuredTaxField)) {
                    if ($this->isPlaceholderTaxValue($value) || !$this->validator->isValidCpfCnpj($value)) {
                        continue;
                    }
                    $value = CentralVixImportadorHelper::formatTaxValueForCustomField(
                        $field,
                        $this->validator->normalizeCpfCnpj($value)
                    );
                    if ($value === '') {
                        continue;
                    }
                } elseif ($this->isPlaceholderTaxValue($value)
                    && !CentralVixImportadorHelper::isKnownNonTaxCustomField($field)) {
                    continue;
                }

                $customFields[$fieldId] = $value;
            }
            if (!empty($customFields)) {
                $data['customfields'] = base64_encode(serialize($customFields));
            }
        }

        if (!empty($data['groupid'])) {
            $data['groupid'] = (int) $data['groupid'];
        }

        return $data;
    }

    private function translateWhmcsMessage($message)
    {
        $message = trim((string) $message);
        if ($message === '') {
            return 'Erro desconhecido ao processar cliente.';
        }

        $exact = [
            'clientareaerrorphonenumber' => 'Telefone inválido ou ausente.',
            'clientareaerrorstate' => 'Estado (UF) inválido ou ausente para o país informado.',
            'clientareaerroraddress1' => 'Endereço (address1) obrigatório ausente.',
            'clientareaerrorcity' => 'Cidade obrigatória ausente.',
            'clientareaerrorpostcode' => 'CEP inválido ou ausente.',
            'clientareaerroremail' => 'E-mail inválido.',
            'clientareaerrorfirstname' => 'Nome obrigatório ausente.',
            'clientareaerrorlastname' => 'Sobrenome obrigatório ausente.',
        ];

        foreach ($exact as $key => $pt) {
            if (stripos($message, $key) !== false) {
                return $pt;
            }
        }

        if (preg_match('/required custom field value for (.+)/i', $message, $m)) {
            return 'Campo personalizado obrigatório ausente: ' . trim($m[1]);
        }

        if (preg_match_all('/required custom field value for ([^,]+)/i', $message, $matches)) {
            $fields = array_map('trim', $matches[1]);
            return 'Campos personalizados obrigatórios ausentes: ' . implode(', ', $fields);
        }

        if (preg_match('/cpf|cnpj.*não é válido|cpf.*not valid|invalid.*tax|tax.?id.*invalid/i', $message)) {
            return 'CPF/CNPJ rejeitado pelo WHMCS. Detalhe: ' . $message;
        }

        return $message;
    }

    private function generateSecurePassword($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*';
        $password = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    private function result($status, $clientId, $message, array $clientData, $rowNumber)
    {
        return [
            'status' => $status,
            'client_id' => (int) $clientId,
            'message' => $message,
            'name' => CentralVixClientMapper::getDisplayName($clientData),
            'email' => $clientData['email'] ?? '',
            'row_number' => (int) $rowNumber,
        ];
    }
}
