# CentralVix Importador de Clientes

Módulo Addon para WHMCS que permite importação em massa de clientes a partir de arquivos **CSV**, **XLSX** e **XLS**, com interface administrativa moderna, mapeamento de campos, processamento em lotes via AJAX e relatório completo.

---

## Requisitos

| Requisito | Versão mínima |
|-----------|---------------|
| WHMCS | 7.x ou superior (testado com 7.x / 8.x) |
| PHP | 7.4+ |
| Extensões PHP | `pdo`, `mbstring`, `fileinfo`, `json` |
| Composer | Recomendado (para suporte Excel) |

### Suporte a Excel

- Arquivos **CSV** funcionam nativamente, sem dependências extras.
- Arquivos **.xlsx** e **.xls** requerem **PhpSpreadsheet**.

---

## Instalação

### 1. Copiar arquivos

Copie a pasta `centralvix_importador` para:

```
/modules/addons/centralvix_importador/
```

### 2. Instalar dependências (Excel)

Acesse a pasta do módulo via SSH e execute:

```bash
cd /caminho/para/whmcs/modules/addons/centralvix_importador
composer install
```

Ou apenas o PhpSpreadsheet (versão 2.x, compatível PHP 8.1–8.2):

```bash
rm -rf vendor composer.lock
composer install --no-dev
```

> **Nota:** A versão 1.29.x está bloqueada pelo Composer por vulnerabilidades de segurança. Use a 2.x definida no `composer.json`.

Se o Composer ainda bloquear por audit:

```bash
composer config audit.block-insecure false
composer install --no-dev
```

### 3. Ativar no WHMCS

1. Acesse **Configurações → Addon Modules**
2. Localize **CentralVix Importador de Clientes**
3. Clique em **Ativar**
4. Configure as permissões de acesso para os administradores desejados
5. Clique em **Configure** para ajustar tamanho máximo de arquivo, lote, moeda padrão etc.

### 4. Permissões de pasta

Certifique-se de que a pasta `storage/temp/` seja gravável pelo PHP:

```bash
chmod 750 storage storage/temp
chown www-data:www-data storage storage/temp
```

---

## Uso

1. Acesse **Addons → CentralVix Importador de Clientes**
2. Vá em **Nova Importação**
3. Siga as 5 etapas:
   - **Upload** — envie CSV ou Excel
   - **Pré-visualização** — confira os primeiros 10 registros
   - **Mapeamento** — relacione colunas aos campos WHMCS
   - **Configurações** — defina regras de importação
   - **Importação** — confirme e acompanhe o progresso em tempo real

---

## Campos WHMCS suportados

| Campo | Descrição |
|-------|-----------|
| firstname | Nome |
| lastname | Sobrenome |
| companyname | Empresa |
| email | E-mail (obrigatório) |
| address1 | Endereço |
| address2 | Complemento |
| city | Cidade |
| state | Estado / UF |
| postcode | CEP |
| country | País (código ISO) |
| phonenumber | Telefone |
| tax_id | CPF/CNPJ (WHMCS 7.10+) |
| password2 | Senha |
| currency | Moeda |
| groupid | Grupo de clientes |
| language | Idioma |
| custom_{id} | Campos personalizados de clientes |

---

## Segurança

- Utiliza **`localAPI('AddClient')`** e **`localAPI('UpdateClient')`** — nunca INSERT SQL direto em `tblclients`
- Validação **CSRF** em todas as requisições AJAX
- Validação de **sessão de administrador**
- Validação de **MIME type** e extensão de arquivo
- Pasta temporária protegida via `.htaccess`
- **Senhas nunca** são registradas nos logs
- O módulo **não** executa DELETE, não altera faturas, serviços ou domínios

---

## Processamento em lotes

A importação processa **10, 25 ou 50** clientes por requisição (configurável), evitando timeout do PHP e congelamento da página. Suporta bases com **5.000+ clientes**.

---

## Banco de dados

Na ativação, o módulo cria:

- `mod_centralvix_importacoes` — histórico de importações
- `mod_centralvix_importacao_logs` — log detalhado por linha

Na desativação, os históricos **são preservados**.

---

## Compatibilidade WHMCS

| Recurso | Observação |
|---------|------------|
| `localAPI` | Compatível com WHMCS 7.x e 8.x |
| `tax_id` | Disponível a partir do WHMCS 7.10; em versões anteriores, mapeie CPF/CNPJ para campo personalizado |
| `generate_token` / `check_token` | Usado quando disponível; fallback para token de sessão próprio |
| Capsule (Illuminate) | Nativo do WHMCS 7+ |

---

## Estrutura de arquivos

```
centralvix_importador/
├── centralvix_importador.php    # Arquivo principal do addon
├── README.md
├── composer.json
├── lib/
│   ├── ModuleHelper.php
│   ├── SpreadsheetReader.php
│   ├── Validator.php
│   ├── ClientMapper.php
│   ├── ClientService.php
│   ├── ImportLogger.php
│   └── Importer.php
├── ajax/
│   ├── upload.php
│   ├── preview.php
│   ├── import.php
│   └── report.php
├── templates/
│   ├── layout_header.php
│   ├── layout_footer.php
│   ├── dashboard.php
│   ├── import.php
│   ├── history.php
│   ├── report.php
│   └── settings.php
├── assets/
│   ├── css/admin.css
│   └── js/importer.js
└── storage/
    ├── .htaccess
    └── temp/
        └── .htaccess
```

---

## Solução de problemas

### "Falha ao salvar arquivo temporário"

A pasta `storage/temp/` não tem permissão de escrita para o PHP. Corrija via SSH:

```bash
cd /home/centralvixcom/public_html/modules/addons/centralvix_importador
mkdir -p storage/temp
chmod -R 775 storage
```

No cPanel/File Manager: clique com botão direito em `storage` → **Change Permissions** → marque **Read/Write/Execute** para Owner e Group (775).

O módulo também tenta salvar em `attachments/cvix_importador/` e no diretório temporário do sistema como fallback.

### "Composer detected issues in your platform" / PHP 8.3

Isso ocorre quando a pasta `vendor/` foi instalada com versões incompatíveis (ex.: PhpSpreadsheet 2.x/3.x/4.x em PHP 8.2).

**Solução:** na pasta do módulo no servidor, reinstale as dependências com a versão correta:

```bash
cd /home/centralvixcom/public_html/modules/addons/centralvix_importador
rm -rf vendor composer.lock
composer install
```

Se não tiver Composer no servidor, instale localmente com PHP 8.2 e envie apenas a pasta `vendor/` gerada.

O módulo **funciona com CSV mesmo sem vendor**. Excel (.xlsx/.xls) exige PhpSpreadsheet **2.x** (PHP 8.1+).

### "PhpSpreadsheet não está instalado"
Execute `composer require phpoffice/phpspreadsheet` na pasta do módulo.

### "Token CSRF inválido"
Recarregue a página do módulo e tente novamente.

### "Falha ao salvar arquivo temporário"
Verifique permissões da pasta `storage/temp/`.

### Erros na API AddClient
Confira se e-mail, moeda e demais campos obrigatórios do WHMCS estão mapeados corretamente.

---

## Licença

Proprietary — CentralVix © 2026
