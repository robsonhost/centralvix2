# CentralVix Atualizador Automático

Servidor central de atualização para sistemas comercializados pela CentralVix. Modular — integra opcionalmente com **CentralVix Licenciamento**.

## Instalação

1. Copie para `/modules/addons/centralvix_updater/`
2. Ative em **Setup → Addon Modules**
3. Cadastre produtos, versões e envie pacotes ZIP
4. Garanta escrita em `storage/` (pacotes, cache, logs)

Desativação **preserva** produtos, versões e pacotes.

## URLs

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_updater` |
| API | `/modules/addons/centralvix_updater/api/` |

## Banco de dados

| Tabela | Função |
|--------|--------|
| `mod_centralvix_updater_products` | Produtos atualizáveis |
| `mod_centralvix_updater_versions` | Versões, checksum, assinatura |
| `mod_centralvix_updater_downloads` | Logs de check/download/falhas |
| `mod_centralvix_updater_settings` | Rate limit, TTL token, segredos |

## Versão (campos)

Produto · Versão · Canal (stable/beta) · Release notes · Arquivo · Tamanho · SHA-256 · Assinatura HMAC · Data · PHP mínimo · WHMCS mínimo (opcional) · Obrigatória · Ativa

## Integração com Licenciamento

Quando `centralvix_licenciamento` está ativo, a API valida licenças via `LicenseConnector`. Configure **slug do produto de licenciamento** no cadastro do produto updater.

Sem licenciamento, produtos podem exigir ou não licença de forma independente.

## Documentação da API

Base: `https://seudominio.com/modules/addons/centralvix_updater/api`

Requisições: **POST** · `Content-Type: application/json`

### POST `/check.php`

**Request:**

```json
{
  "product": "meu-sistema",
  "current_version": "1.0.0",
  "channel": "stable",
  "license_key": "CVIX-XXXXXX-XXXXXX-XXXXXX-XXXXXX",
  "domain": "cliente.com.br"
}
```

**Response (200):**

```json
{
  "success": true,
  "data": {
    "update_available": true,
    "current_version": "1.0.0",
    "latest_version": "1.2.0",
    "download": {
      "url": "https://.../api/download.php",
      "token": "64-char-hex",
      "expires_at": "2026-08-09 19:00:00",
      "method": "POST"
    },
    "checksum": {
      "sha256": "...",
      "signature": "...",
      "size": 1048576
    },
    "release_notes": "<p>Correções...</p>",
    "required": false,
    "minimum_php": "8.1",
    "minimum_whmcs": "8.0",
    "channel": "stable"
  }
}
```

Respostas assinadas: header `X-CVIX-Signature` (HMAC com `license_key` quando informada).

Respostas genéricas em falha de licença/produto — **anti-enumeração**.

### POST `/download.php`

**Request:**

```json
{
  "token": "token-recebido-no-check"
}
```

Retorna stream `application/zip`. Token:

- Uso único
- Expiração configurável (padrão 15 min)
- Vinculado ao IP da emissão
- **Não expõe caminho físico** do pacote

Headers úteis: `X-CVIX-Version`, `X-CVIX-SHA256`

## Segurança

| Medida | Implementação |
|--------|----------------|
| CSRF | Admin POST |
| Path traversal | Storage isolado + validação realpath |
| ZIP Slip | Scan no upload; proteção no cliente exemplo |
| Rate limit | Por IP (configurável) |
| SHA-256 | Hash no upload; validação no cliente |
| Assinatura pacote | HMAC-SHA256 server-side |
| Download | Token temporário, não URL permanente |
| Logs | DB + arquivo em `storage/logs/` |

## Cliente de exemplo

`/examples/client-updater/` — consulta, download, SHA-256, backup, manutenção, rollback.

**Padrão DRY_RUN** — não sobrescreve produção sem `CVIX_APPLY=1`.

## Dashboard admin

Produtos · Versões · Downloads · Checks · Falhas

## Hooks WHMCS

| Hook | Ação |
|------|------|
| `AfterCronJob` | Purga tokens expirados |

## Pacotes

- Apenas ZIP (máx. 500 MB)
- Validado MIME + ZIP Slip
- Armazenado em `storage/packages/` (bloqueado via `.htaccess`)
- **Não extraído** no servidor após upload
