# CentralVix Streaming Manager

Addon WHMCS para gerenciamento de streaming de áudio (SHOUTcast, Icecast) e preparação para vídeo, com arquitetura de **providers desacoplados**.

## Requisitos

| Componente | Versão |
|------------|--------|
| WHMCS | 7.x / 8.x |
| PHP | 7.4+ (recomendado 8.2+) |
| Extensões | `curl`, `openssl`, `json`, `simplexml`, `pdo_mysql` |

## Instalação

1. Copie para `/modules/addons/centralvix_streaming_manager/`
2. **Configurações → Addon Modules** → Ative o módulo
3. Configure timeout, retenção de stats e IDs de produtos (opcional)
4. **Addons → CentralVix Streaming Manager** — cadastre servidores e contas
5. Área do cliente: `index.php?m=centralvix_streaming_manager`

Desativar o módulo **não remove** dados (sem DROP TABLE).

## Arquitetura de providers

```
StreamingServerFactory
├── ShoutcastProvider   — stats admin.cgi / 7.html
├── IcecastProvider     — stats status-json.xsl, killsource admin
└── VideoProvider       — placeholder + webhook genérico
```

Cada provider implementa capacidades explícitas via `getCapabilities()`. Operações não suportadas retornam erro claro — **nunca simulam sucesso**.

### APIs oficialmente utilizadas

| Plataforma | Leitura (stats) | Ações admin |
|------------|-----------------|-------------|
| **Icecast 2** | `GET /status-json.xsl` | `GET /admin/killsource.xsl` (HTTP auth) |
| **SHOUTcast DNAS** | `GET /admin.cgi?mode=viewxml` ou `/7.html` | `admin.cgi` (killsrc, restart) com senha admin |

Criação de contas, alteração de bitrate e limites **não existem** na API padrão Icecast/SHOUTcast. Para isso configure **URL de provisionamento** (webhook JSON) no servidor — o addon delega ao seu painel/API.

Payload webhook exemplo:

```json
{
  "action": "create",
  "account_id": 1,
  "username": "cv123s456",
  "mount": "/stream",
  "port": 8000
}
```

## Servidores

Campos: nome, tipo, hostname, portas API/stream, SSL, usuário admin, senha/token (**criptografada AES-256-CBC**), capacidade, localização, URL provisionamento.

Senhas **nunca** aparecem na interface nem nos logs.

## Contas

Vinculadas a cliente WHMCS, serviço (`tblhosting`), servidor, mountpoint, bitrate, ouvintes, AutoDJ, status.

## Provisionamento WHMCS

Hooks registrados (confirmados WHMCS 7/8):

- `AfterModuleCreate`
- `AfterModuleSuspend`
- `AfterModuleUnsuspend`
- `AfterModuleTerminate`

Filtragem opcional por IDs de produtos (`product_ids` nas configurações).

### Server module vs Addon

| Abordagem | Uso |
|-----------|-----|
| **Este addon** | Painel admin, área cliente, stats, providers, webhooks |
| **Server module** (`/modules/servers/`) | Integração nativa no fluxo de pedido WHMCS |

Recomendação: mantenha este addon como núcleo e, se necessário, crie um server module fino que chame `CentralVixStreamingProvisioningService`.

## Área do cliente

- Status online/offline, ouvintes, pico, bitrate
- URL streaming, dados encoder (senha oculta)
- Player HTML5
- Botões Iniciar/Parar/Reiniciar/Alterar senha **somente se o provider suportar**

## Estatísticas

Coleta via cron (`AfterCronJob`), intervalo configurável. Retenção padrão 30 dias (purge automático).

## Cron

```bash
*/5 * * * * php -q /caminho/whmcs/crons/cron.php
```

## Tabelas

- `mod_centralvix_stream_servers`
- `mod_centralvix_stream_accounts`
- `mod_centralvix_stream_stats`
- `mod_centralvix_stream_logs`
- `mod_centralvix_stream_settings`

## Segurança

- CSRF (admin + client)
- Credenciais criptografadas
- Validação hostname/porta
- Timeout HTTP configurável
- Logs sanitizados (sem senhas/tokens)

Desenvolvido para **CentralVix**.
