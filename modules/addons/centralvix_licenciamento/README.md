# CentralVix Licenciamento de Sistemas

Gerenciamento de licenças para produtos comercializados pela CentralVix, com API de validação, grace period e integração WHMCS.

## Instalação

1. Copie para `/modules/addons/centralvix_licenciamento/`
2. Ative em **Setup → Addon Modules**
3. Configure produtos licenciáveis e permissões admin/cliente
4. Escrita em `storage/`

## URLs

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_licenciamento` |
| Cliente | `index.php?m=centralvix_licenciamento` |
| API base | `/modules/addons/centralvix_licenciamento/api/` |

## Status da licença

Pendente · Ativa · Suspensa · Expirada · Cancelada

## Chaves de licença

Formato: `CVIX-XXXXXX-XXXXXX-XXXXXX-XXXXXX`

Geradas com `random_bytes` — **nunca** IDs incrementais.

Lookup interno via hash SHA-256 (pepper servidor) para reduzir exposição.

## Banco de dados

| Tabela | Função |
|--------|--------|
| `mod_centralvix_license_products` | Produtos licenciáveis |
| `mod_centralvix_licenses` | Licenças |
| `mod_centralvix_license_activations` | Domínio/IP por ativação |
| `mod_centralvix_license_logs` | Auditoria |
| `mod_centralvix_license_settings` | Configurações e segredos |

Desativação **preserva** dados.

## Integração WHMCS (hooks oficiais)

| Hook | Ação |
|------|------|
| `AfterModuleCreate` | Cria licença (se auto_provision ativo e produto mapeado) |
| `AfterModuleSuspend` | Suspende licença |
| `AfterModuleUnsuspend` | Reativa licença |
| `AfterModuleTerminate` | Cancela licença |
| `AfterCronJob` | Marca licenças expiradas |

Confirme compatibilidade com sua versão WHMCS (7.x/8.x).

## Grace period (cache offline)

Configurável por produto: **24h, 48h ou 72h**.

Quando o servidor de licenças está inacessível, o cliente PHP pode usar cache local dentro do grace period — **não derruba o sistema imediatamente** por falha de rede temporária.

## Documentação da API

Base: `https://seudominio.com/modules/addons/centralvix_licenciamento/api`

Todas as requisições: **POST**, `Content-Type: application/json`

### POST `/validate.php`

Valida licença sem consumir slot de ativação.

```json
{
  "license_key": "CVIX-XXXXXX-XXXXXX-XXXXXX-XXXXXX",
  "domain": "meusite.com.br"
}
```

**Resposta (200):**

```json
{
  "success": true,
  "data": {
    "valid": true,
    "status": "ativa",
    "status_label": "Ativa",
    "product": "Meu Sistema",
    "domain": "meusite.com.br",
    "expires_at": "2027-01-15 23:59:59",
    "grace_period_hours": 24,
    "activations_used": 1,
    "max_activations": 2,
    "checked_at": "2026-08-09T18:00:00-03:00"
  }
}
```

**Headers de resposta:**

- `X-CVIX-Signature`: HMAC-SHA256 do body JSON
- `X-CVIX-Signature-Alg`: HMAC-SHA256

A assinatura usa `HMAC-SHA256(body, SHA256("cvix-response-v1|" + license_key))`.
O cliente verifica com a própria chave de licença — **sem api_secret no código distribuído**.

### POST `/activate.php`

Registra ativação em domínio/IP.

```json
{
  "license_key": "CVIX-...",
  "domain": "meusite.com.br"
}
```

O **IP é detectado no servidor** (`REMOTE_ADDR` / X-Forwarded-For) — **não confia em IP enviado via POST**.

### POST `/deactivate.php`

Revoga ativação de um domínio.

```json
{
  "license_key": "CVIX-...",
  "domain": "meusite.com.br"
}
```

### Rate limiting

Padrão: **60 req/min/IP** (configurável). Resposta `429` quando excedido.

### Anti-enumeração

Respostas genéricas para chaves inválidas. Delay aleatório em requisições malformadas.

## Cliente PHP de exemplo

Arquivo: `examples/validate_client.php`

```bash
CVIX_LICENSE_KEY="CVIX-..." CVIX_DOMAIN="meusite.com.br" php examples/validate_client.php
```

## Admin

- Criar licenças manualmente
- Ativar / Suspender / Cancelar / Renovar
- Trocar domínio / Resetar ativações
- Todas as ações registradas em log

## Cliente WHMCS

- Visualizar produto, chave, domínio, status, validade, ativações
- Copiar chave
- Histórico de eventos

## Segurança

- CSRF (admin/cliente)
- XSS (escape)
- Rate limit API
- Hash SHA-256 documentos e lookup de chaves
- Pepper servidor (`server_pepper`) nunca exposto
- API secret apenas para integrações server-side (admin settings)
- Prepared statements (Capsule)
- IP de ativação obtido server-side
- Logs de todas as chamadas API

## ProviderInterface

`lib/ProviderInterface.php` — stub para integração futura com provedor certificado externo.

## Estrutura

```
centralvix_licenciamento/
├── centralvix_licenciamento.php
├── hooks.php
├── README.md
├── lib/
├── admin/
├── client/
├── api/
├── examples/
├── storage/
└── assets/
```

## Versão

1.0.0 — CentralVix
