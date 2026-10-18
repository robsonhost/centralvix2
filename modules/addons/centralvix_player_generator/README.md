# CentralVix Player Generator

Módulo WHMCS para criação de players personalizados de rádio/stream com URL pública, iframe e código HTML de incorporação.

## Instalação

1. Copie a pasta `centralvix_player_generator` para `/modules/addons/`.
2. Em **Configurações → Addon Modules**, ative **CentralVix Player Generator**.
3. Configure permissões de acesso (admin e clientes).
4. Garanta permissão de escrita em `/modules/addons/centralvix_player_generator/storage/`.

## Acesso

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_player_generator` |
| Cliente | `index.php?m=centralvix_player_generator` |
| Player público | `/modules/addons/centralvix_player_generator/public/player.php?h={hash}` |

## Funcionalidades

### Cliente
- Criar, editar, duplicar e excluir players (somente os próprios)
- Builder visual com preview em tempo real
- Campos: nome, stream, logo, fundo, cores, tema claro/escuro, textos, toggles de UI, redes sociais
- Gerar URL pública, iframe e HTML de embed
- Ativar/desativar player

### Admin
- Dashboard com totais (players, ativos, clientes, visualizações)
- Listagem global de players
- Temas padrão CentralVix
- Habilitar/desabilitar contagem de visualizações

### Player público
- Responsivo (desktop e mobile)
- HTML5 Audio/Video
- MP3, AAC (quando suportado), HLS via hls.js
- Sem autoplay forçado com som (respeita bloqueio do navegador)
- Metadados via proxy server-side (Icecast, Shoutcast; AzuraCast preparado)
- Falha de metadados não interrompe reprodução

## Banco de dados

| Tabela | Descrição |
|--------|-----------|
| `mod_centralvix_players` | Configuração dos players |
| `mod_centralvix_player_themes` | Temas padrão |
| `mod_centralvix_player_socials` | Redes sociais por player |
| `mod_centralvix_player_settings` | Configurações internas |

Desativação do módulo **não remove** as tabelas.

## Segurança

- CSRF em formulários e AJAX (admin/cliente)
- Validação de URLs de stream e redes sociais
- Upload seguro de imagens (MIME, tamanho, extensão)
- Storage protegido com `.htaccess`
- Hash público não previsível (`public_hash`)
- Isolamento por `client_id`
- Escape XSS em toda saída HTML
- Metadados buscados no servidor (sem contorno inseguro de CORS no browser)

## Metadados

Adapters em `lib/StreamMetadata.php`:
- **Icecast** — `/status-json.xsl`
- **Shoutcast** — `/7.html`
- **AzuraCast** — stub para integração futura

Endpoint público: `public/metadata.php`

## Embed

Exemplo gerado automaticamente:

```html
<iframe src="https://seudominio.com/modules/addons/centralvix_player_generator/public/player.php?h=abc123..." width="360" height="420" frameborder="0" allow="autoplay" style="max-width:100%;border:0;border-radius:12px;" title="Minha Rádio"></iframe>
```

## Configurações do módulo

- Upload máximo de imagem (MB)
- Permitir streams localhost (desenvolvimento)
- Largura/altura padrão do iframe

## Estrutura

```
centralvix_player_generator/
├── centralvix_player_generator.php
├── hooks.php
├── README.md
├── lib/
├── admin/
├── client/
├── public/
├── ajax/
├── assets/
├── storage/
├── templates/
└── lang/
```

## Versão

1.0.0 — CentralVix
