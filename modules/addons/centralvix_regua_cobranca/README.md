# CentralVix Régua de Cobrança

Addon WHMCS para automação completa de lembretes de cobrança com réguas configuráveis, e-mail, WhatsApp e histórico de disparos.

## Requisitos

| Componente | Versão |
|------------|--------|
| WHMCS | 7.x / 8.x |
| PHP | 7.4+ (recomendado 8.2+) |
| Extensões | `curl`, `json`, `pdo_mysql` |

## Instalação

1. Copie a pasta para:

   ```
   /modules/addons/centralvix_regua_cobranca/
   ```

2. **Configurações → Addon Modules** → Ative **CentralVix Régua de Cobrança**

3. Clique em **Configure** e defina:
   - Nome da empresa (`{empresa}`)
   - Horário global de envio (ex: 08:00 — 20:00)
   - Intervalo do cron (minutos)
   - WhatsApp: endpoint, token, instância, método HTTP, headers JSON

4. Acesse **Addons → CentralVix Régua de Cobrança**

Na ativação são criadas **9 réguas padrão** (7/5/3/1 dias antes, no vencimento, 1/3/5/10 dias após) e templates iniciais.

## Réguas padrão

| Etapa | Timing |
|-------|--------|
| 7 dias antes | Antes |
| 5 dias antes | Antes |
| 3 dias antes | Antes |
| 1 dia antes | Antes |
| No vencimento | No dia |
| 1 dia atrasado | Depois |
| 3 dias atrasado | Depois |
| 5 dias atrasado | Depois |
| 10 dias atrasado | Depois |

Cada régua pode enviar **e-mail**, **WhatsApp** ou **ambos**, com horário próprio e filtros por produto/cliente.

## Placeholders

| Placeholder | Descrição |
|-------------|-----------|
| `{cliente}` | Nome completo |
| `{nome}` | Primeiro nome |
| `{empresa}` | Nome da empresa |
| `{fatura}` | ID da fatura |
| `{valor}` | Valor formatado |
| `{vencimento}` | Data dd/mm/aaaa |
| `{dias_atraso}` | Dias em atraso |
| `{link_fatura}` | URL viewinvoice.php |
| `{link_pagamento}` | URL de pagamento |
| `{produto}` | Produto principal |

Use o **visualizador** na tela de templates antes de salvar.

## Idempotência

Cada disparo usa chave lógica:

```
invoice_id + rule_id + data (Y-m-d)
```

Por canal (e-mail / WhatsApp). A mesma etapa **nunca é enviada duas vezes** no mesmo dia para a mesma fatura.

## Cron

### Hook WHMCS (automático)

O módulo registra `AfterCronJob`. Configure o cron padrão do WHMCS:

```bash
*/5 * * * * php -q /caminho/para/whmcs/crons/cron.php
```

O intervalo entre execuções da régua é controlado pelo campo **Intervalo cron (minutos)** (padrão: 60).

### Cron dedicado (alternativa)

```bash
0 * * * * php -q /caminho/para/whmcs/modules/addons/centralvix_regua_cobranca/cron.php
```

### Execução manual

```bash
php /caminho/para/whmcs/modules/addons/centralvix_regua_cobranca/cron.php
```

Mensagens **não são enviadas** fora da janela global (08:00–20:00 por padrão) nem fora do horário de cada régua.

## WhatsApp

Configure no addon:

- **Endpoint** — URL da API
- **Token** — nunca registrado em logs
- **Instância** — nome da instância
- **Método HTTP** — POST ou GET
- **Headers extras** — JSON opcional, ex: `{"X-Api-Key":"sua-chave"}`

Payload padrão enviado:

```json
{
  "phone": "5511999999999",
  "message": "Texto renderizado",
  "instance": "CentralVix"
}
```

Teste em **Configurações** dentro do módulo.

## Pagamento confirmado

Hook `InvoicePaid`:

- Interrompe automaticamente lembretes (faturas pagas não entram na régua)
- Opcionalmente envia mensagem de confirmação (template "Pagamento confirmado")

Ative em **Enviar confirmação de pagamento** nas configurações do addon.

## Tabelas

- `mod_centralvix_regua_rules`
- `mod_centralvix_regua_templates`
- `mod_centralvix_regua_logs`
- `mod_centralvix_regua_settings`

**Desativação:** dados preservados (sem DROP TABLE).

## Segurança

- CSRF em AJAX admin
- Sessão administrativa obrigatória
- Sanitização e escape HTML
- Logs sem credenciais
- Timeout HTTP 20s no WhatsApp
- Não altera status de faturas no WHMCS

## Hooks utilizados

| Hook | Função |
|------|--------|
| `AdminAreaPage` | Endpoints AJAX admin |
| `AfterCronJob` | Execução automática da régua |
| `InvoicePaid` | Confirmação de pagamento |

## Estrutura

```
centralvix_regua_cobranca/
├── centralvix_regua_cobranca.php
├── hooks.php
├── cron.php
├── README.md
├── lib/
├── admin/
├── ajax/
├── templates/
├── assets/
└── lang/
```

Desenvolvido para **CentralVix**.
