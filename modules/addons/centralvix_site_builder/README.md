# CentralVix Site Builder

Central de produção e entrega de sites para clientes CentralVix. Gerencia o fluxo completo desde o pedido até a publicação — **não** é um construtor drag-and-drop tipo Wix.

## Instalação

1. Copie `centralvix_site_builder` para `/modules/addons/`.
2. Ative em **Setup → Addon Modules**.
3. Configure permissões admin e clientes.
4. Garanta escrita em `/modules/addons/centralvix_site_builder/storage/`.

## Acesso

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_site_builder` |
| Cliente | `index.php?m=centralvix_site_builder` |

## Fluxo do projeto

1. Pedido / criação do projeto  
2. Briefing do cliente  
3. Recebimento de materiais (logo, fotos, textos, PDF)  
4. Design → Desenvolvimento → Revisão  
5. Aprovação ou alterações do cliente  
6. Publicação → Entrega (finalizado)

## Status disponíveis

- Aguardando briefing  
- Briefing recebido  
- Aguardando materiais  
- Em design  
- Em desenvolvimento  
- Em revisão  
- Aguardando cliente  
- Alterações solicitadas  
- Aprovado  
- Publicando  
- Finalizado  

## Funcionalidades

### Admin
- Dashboard com métricas (ativos, aguardando cliente, atrasados, em dev, aprovados, finalizados)
- CRUD de projetos vinculados a clientes WHMCS
- Alteração de status com timeline
- Visualização de briefing, arquivos e aprovações
- Templates de briefing e campos customizados
- Configuração de e-mail e WhatsApp (preparado)

### Cliente
- Painel visual com progresso, prazo e próxima etapa
- Formulário de briefing (campos padrão + extras)
- Upload seguro de materiais por categoria
- Comentários em arquivos
- Aprovar etapa ou solicitar alteração (comentário obrigatório em alterações)
- Timeline completa do projeto

## Banco de dados

| Tabela | Descrição |
|--------|-----------|
| `mod_centralvix_site_projects` | Projetos |
| `mod_centralvix_site_briefings` | Briefings |
| `mod_centralvix_site_files` | Arquivos enviados |
| `mod_centralvix_site_comments` | Comentários |
| `mod_centralvix_site_approvals` | Aprovações (IP, data, etapa) |
| `mod_centralvix_site_timeline` | Histórico |
| `mod_centralvix_site_settings` | Configurações |

Desativação **preserva** todos os dados.

## Notificações

Eventos com e-mail (configurável):

- Projeto criado  
- Solicitação de material  
- Nova versão / revisão  
- Aguardando aprovação  
- Alteração solicitada  
- Projeto aprovado  
- Projeto concluído  

WhatsApp: preparado via log interno quando habilitado (integração externa conforme seu gateway).

## Segurança

- Cliente acessa **somente** projetos próprios (`client_id`)
- Uploads em storage privado com `.htaccess`
- Download via controller autorizado (`cvix_site_download`)
- Proteção path traversal no resolve de arquivos
- CSRF em POST/AJAX
- Escape XSS
- Validação MIME e tamanho por categoria
- Registro de IP em aprovações

## Hook opcional

Com **Criar projeto ao ativar serviço** habilitado, o hook `AfterModuleCreate` cria projeto automaticamente vinculado ao serviço WHMCS.

## Estrutura

```
centralvix_site_builder/
├── centralvix_site_builder.php
├── hooks.php
├── README.md
├── lib/
├── admin/
├── client/
├── ajax/
├── assets/
├── storage/
├── templates/
└── lang/
```

## Versão

1.0.0 — CentralVix
