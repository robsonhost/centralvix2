<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

class CentralVixClientMapper
{
    public static function getWhmcsFields()
    {
        return [
            'firstname' => 'Nome (firstname)',
            'lastname' => 'Sobrenome (lastname)',
            'companyname' => 'Empresa (companyname)',
            'email' => 'E-mail (email)',
            'address1' => 'Endereço (address1)',
            'address2' => 'Complemento (address2)',
            'city' => 'Cidade (city)',
            'state' => 'Estado (state)',
            'postcode' => 'CEP (postcode)',
            'country' => 'País (country)',
            'phonenumber' => 'Telefone (phonenumber)',
            'tax_id' => 'CPF/CNPJ (tax_id)',
            'password2' => 'Senha (password2)',
            'currency' => 'Moeda (currency)',
            'groupid' => 'Grupo (groupid)',
            'language' => 'Idioma (language)',
        ];
    }

    public static function getAutoMappingRules()
    {
        return [
            'firstname' => ['nome', 'first name', 'firstname', 'primeiro nome', 'name'],
            'lastname' => ['sobrenome', 'last name', 'lastname', 'ultimo nome', 'surname'],
            'companyname' => ['empresa', 'company', 'companyname', 'razao social', 'razão social', 'nome fantasia'],
            'email' => ['email', 'e-mail', 'e mail', 'mail', 'correio'],
            'address1' => ['endereco', 'endereço', 'address', 'address1', 'rua', 'logradouro'],
            'address2' => ['complemento', 'address2', 'apto', 'bloco', 'referencia', 'referência'],
            'city' => ['cidade', 'city', 'municipio', 'município'],
            'state' => ['estado', 'state', 'uf', 'provincia', 'província'],
            'postcode' => ['cep', 'postcode', 'zip', 'codigo postal', 'código postal'],
            'country' => ['pais', 'país', 'country', 'nacionalidade'],
            'phonenumber' => ['celular', 'whatsapp', 'telefone', 'phone', 'phonenumber', 'mobile', 'fone', 'tel'],
            'tax_id' => ['cpf', 'cnpj', 'tax_id', 'documento', 'identificacao fiscal'],
            'password2' => ['senha', 'password', 'password2'],
            'currency' => ['moeda', 'currency'],
            'groupid' => ['grupo', 'group', 'groupid', 'grupo cliente'],
            'language' => ['idioma', 'language', 'lang'],
        ];
    }

    public static function detectMapping(array $columns, array $customFields = [])
    {
        $mapping = [];
        $rules = self::getAutoMappingRules();
        $usedTargets = [];

        foreach ($columns as $column) {
            $normalizedCol = self::normalize($column);
            $matched = false;

            foreach ($rules as $field => $aliases) {
                if (in_array($field, $usedTargets, true)) {
                    continue;
                }
                foreach ($aliases as $alias) {
                    if (self::shouldSkipColumnForField($normalizedCol, $field)) {
                        continue;
                    }
                    if ($normalizedCol === self::normalize($alias) || strpos($normalizedCol, self::normalize($alias)) !== false) {
                        $mapping[$column] = $field;
                        $usedTargets[] = $field;
                        $matched = true;
                        break 2;
                    }
                }
            }

            if (!$matched) {
                foreach ($customFields as $cf) {
                    if (CentralVixImportadorHelper::isTaxCustomField($cf)) {
                        continue;
                    }
                    $cfKey = 'custom_' . $cf->id;
                    if (in_array($cfKey, $usedTargets, true)) {
                        continue;
                    }
                    $cfName = self::normalize($cf->fieldname);
                    if ($normalizedCol === $cfName || strpos($normalizedCol, $cfName) !== false || strpos($cfName, $normalizedCol) !== false) {
                        $mapping[$column] = $cfKey;
                        $usedTargets[] = $cfKey;
                        break;
                    }
                }
            }
        }

        return $mapping;
    }

    public static function applyMapping(array $rowData, array $mapping, array $defaults = [])
    {
        $client = [
            'firstname' => '',
            'lastname' => '',
            'companyname' => '',
            'email' => '',
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'postcode' => '',
            'country' => '',
            'phonenumber' => '',
            'tax_id' => '',
            'password2' => '',
            'currency' => '',
            'groupid' => '',
            'language' => '',
            'customfields' => [],
        ];

        foreach ($defaults as $key => $value) {
            if ($value !== '' && $value !== null && isset($client[$key])) {
                $client[$key] = $value;
            }
        }

        foreach ($mapping as $column => $target) {
            if (!isset($rowData[$column])) {
                continue;
            }
            $value = $rowData[$column];
            if (strpos($target, 'custom_') === 0) {
                $fieldId = (int) str_replace('custom_', '', $target);
                $client['customfields'][$fieldId] = $value;
            } elseif (isset($client[$target])) {
                $client[$target] = $value;
            }
        }

        return self::enhanceFromExportColumns($client, $rowData);
    }

    /**
     * Enriquece dados com colunas comuns de exportações (ex.: NotificaZap).
     */
    public static function enhanceFromExportColumns(array $client, array $rowData)
    {
        $phone = trim($client['phonenumber'] ?? '');
        if ($phone === '') {
            $phone = self::firstColumnValue($rowData, ['celular', 'whatsapp', 'telefone', 'fone', 'mobile', 'phone']);
        }
        if ($phone !== '') {
            $client['phonenumber'] = $phone;
        }

        $taxId = trim($client['tax_id'] ?? '');
        if ($taxId === '') {
            $taxId = self::firstColumnValue($rowData, ['cpf']);
        }
        if ($taxId === '') {
            $taxId = self::firstColumnValue($rowData, ['cnpj']);
        }
        if ($taxId === '') {
            $taxId = self::firstColumnValue($rowData, ['documento', 'doc']);
        }
        if ($taxId !== '') {
            $client['tax_id'] = $taxId;
        }

        $street = self::firstColumnValue($rowData, ['endereco', 'endereço', 'address', 'address1', 'logradouro', 'rua']);
        $number = self::firstColumnValue($rowData, ['numero', 'número', 'number', 'n']);
        $district = self::firstColumnValue($rowData, ['bairro', 'district', 'neighborhood']);
        $complement = self::firstColumnValue($rowData, ['complemento', 'address2', 'apto', 'bloco']);

        if ($street !== '') {
            $addressParts = [$street];
            if ($number !== '') {
                $addressParts[] = $number;
            }
            if ($district !== '') {
                $addressParts[] = $district;
            }
            $client['address1'] = implode(', ', $addressParts);
        }

        if ($complement !== '' && trim($client['address2'] ?? '') === '') {
            $client['address2'] = $complement;
        }

        $firstname = trim($client['firstname'] ?? '');
        $lastname = trim($client['lastname'] ?? '');
        if ($firstname === '' && $lastname === '') {
            $fullName = self::firstColumnValue($rowData, ['nome completo', 'name', 'full name', 'cliente']);
            if ($fullName !== '') {
                $parts = preg_split('/\s+/', $fullName, 2);
                $client['firstname'] = $parts[0];
                $client['lastname'] = $parts[1] ?? 'Importado';
            }
        }

        $company = trim($client['companyname'] ?? '');
        if ($company === '') {
            $tipo = mb_strtolower(self::firstColumnValue($rowData, ['tipo', 'type']), 'UTF-8');
            if ($tipo === 'company' || $tipo === 'empresa' || $tipo === 'pj') {
                $company = self::firstColumnValue($rowData, ['empresa', 'company', 'razao social', 'razão social']);
                if ($company !== '') {
                    $client['companyname'] = $company;
                }
            }
        }

        return $client;
    }

    private static function shouldSkipColumnForField($normalizedCol, $field)
    {
        if ($field === 'firstname') {
            return strpos($normalizedCol, 'completo') !== false
                || strpos($normalizedCol, 'full name') !== false
                || strpos($normalizedCol, 'nome fantasia') !== false;
        }

        if ($field === 'address2') {
            return strpos($normalizedCol, 'numero') !== false
                || strpos($normalizedCol, 'number') !== false;
        }

        return false;
    }

    private static function firstColumnValue(array $rowData, array $aliases)
    {
        $normalizedRow = [];
        foreach ($rowData as $column => $value) {
            $normalizedRow[self::normalize($column)] = [
                'column' => $column,
                'value' => trim((string) $value),
            ];
        }

        foreach ($aliases as $alias) {
            $normalizedAlias = self::normalize($alias);
            foreach ($normalizedRow as $normalizedCol => $item) {
                if ($item['value'] === '') {
                    continue;
                }
                if (strlen($normalizedAlias) <= 3) {
                    if ($normalizedCol !== $normalizedAlias) {
                        continue;
                    }
                } elseif ($normalizedCol !== $normalizedAlias && strpos($normalizedCol, $normalizedAlias) === false) {
                    continue;
                }
                return $item['value'];
            }
        }

        return '';
    }

    public static function getDisplayName(array $clientData)
    {
        $name = trim($clientData['firstname'] . ' ' . $clientData['lastname']);
        if ($name === '' && !empty($clientData['companyname'])) {
            return $clientData['companyname'];
        }
        if ($name === '' && !empty($clientData['email'])) {
            return $clientData['email'];
        }
        return $name ?: 'Sem nome';
    }

    private static function normalize($str)
    {
        $str = mb_strtolower(trim((string) $str), 'UTF-8');
        $str = preg_replace('/[áàâãä]/u', 'a', $str);
        $str = preg_replace('/[éèêë]/u', 'e', $str);
        $str = preg_replace('/[íìîï]/u', 'i', $str);
        $str = preg_replace('/[óòôõö]/u', 'o', $str);
        $str = preg_replace('/[úùûü]/u', 'u', $str);
        $str = preg_replace('/[ç]/u', 'c', $str);
        $str = preg_replace('/[^a-z0-9]+/', ' ', $str);
        return trim($str);
    }
}
