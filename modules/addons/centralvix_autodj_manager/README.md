# CentralVix AutoDJ Manager

Gerenciador de AutoDJ para clientes de streaming de áudio — biblioteca, playlists, programação e quota — com arquitetura de **providers** desacoplados.

## Requisitos

- WHMCS 7.x / 8.x
- PHP 7.4+ (8.2+ recomendado)
- Extensões: `curl`, `fileinfo`, `openssl`, `json`

## Instalação

1. Copie para `/modules/addons/centralvix_autodj_manager/`
2. Ative em **Configurações → Addon Modules**
3. Configure upload máximo, AAC e quota padrão
4. Crie contas AutoDJ em **Addons → CentralVix AutoDJ Manager → Contas**
5. Cliente acessa: `index.php?m=centralvix_autodj_manager`

Permissões storage:
```bash
chmod 750 modules/addons/centralvix_autodj_manager/storage
chmod 750 modules/addons/centralvix_autodj_manager/storage/accounts
```

Desativar **não remove** tabelas nem arquivos.

## AutoDJProviderInterface

Providers em `lib/StreamingConnector.php`:

| Provider | Modo |
|----------|------|
| **none** | Local — arquivos no WHMCS |
| **azuracast** | Requer `base_url` + `api_key` (+ `station_id`) |
| **centovacast** | Requer config do painel (estender provider) |
| **sonicpanel** | Requer `api_url` + `api_token` |

Sem API configurada, operações remotas retornam erro claro — **não inventa endpoints**.

AzuraCast testa apenas `/api/status` quando credenciais existem (documentação oficial).

## Upload

- MP3 (sempre)
- AAC/M4A se **Permitir AAC** ativo
- Validação: MIME, extensão, tamanho, bloqueio de PHP/exe
- Armazenamento: `storage/accounts/{id}/` com `.htaccess Deny`
- Nomes gerados internamente (hash) — **sem path do navegador**

## Quota

Por conta: `quota_mb` (ex: 5120 = 5 GB). Dashboard cliente mostra utilizado, disponível e percentual.

## Playlists

Nome, descrição, tipo, faixas, ordem, aleatório, ativa/inativa.

## Agendamento

Data opcional, dias da semana, hora início/fim, playlist, prioridade, tipos (programa, relógio, comercial, vinheta horária, programete).

**Detecção de conflitos** — alerta visual quando horários se sobrepõem.

## Segurança

- Isolamento por `client_id` em toda operação
- `assertAccountOwner()` em uploads/listagens
- CSRF admin e client
- Path traversal bloqueado
- Senhas/tokens de provider não logados

## Tabelas

- `mod_centralvix_autodj_accounts`
- `mod_centralvix_autodj_media`
- `mod_centralvix_autodj_playlists`
- `mod_centralvix_autodj_playlist_items`
- `mod_centralvix_autodj_schedules`
- `mod_centralvix_autodj_settings`
- `mod_centralvix_autodj_logs`

## Integração streaming

Este addon **não** implementa servidor de streaming. Integre com **CentralVix Streaming Manager** ou providers externos via webhook/API configurada na conta.

Desenvolvido para **CentralVix**.
