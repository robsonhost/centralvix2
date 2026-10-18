# CentralVix Project Manager

Gerenciamento de projetos WHMCS para sites, aplicativos, sistemas, integrações, infraestrutura, streaming e serviços personalizados.

## Instalação

1. Copie para `/modules/addons/centralvix_project_manager/`
2. Ative em **Setup → Addon Modules**
3. Configure permissões admin/cliente
4. Permissão de escrita em `storage/`

## URLs

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_project_manager` |
| Cliente | `index.php?m=centralvix_project_manager` |

## Tipos de projeto

Sites · Aplicativos · Sistemas · Integrações · Infraestrutura · Streaming · Serviços personalizados

## Status do projeto

Planejamento · Em andamento · Aguardando cliente · Pausado · Em revisão · Concluído · Cancelado

## Kanban (tarefas)

Colunas: **A fazer** · **Em andamento** · **Revisão** · **Concluído**

Drag-and-drop nativo HTML5 com persistência via AJAX (`status.php` → `reorderKanban`).

## Visibilidade (RBAC)

| Recurso | Interno | Cliente |
|---------|---------|---------|
| Projeto | Sempre admin | `client_visible=1` |
| Tarefa | `visibility=internal` | `visibility=client` |
| Comentário | `visibility=internal` | `visibility=public` |
| Anexo | `visibility=internal` | `visibility=client` |
| Marco | padrão interno | `client_visible=1` |

Comentários **internos nunca** aparecem na área cliente.

## Integração WHMCS

Campos opcionais por projeto (sem alterar tabelas core):

- `client_id`
- `service_id`
- `invoice_id`
- `ticket_id`

## Banco de dados

| Tabela | Uso |
|--------|-----|
| `mod_centralvix_pm_projects` | Projetos |
| `mod_centralvix_pm_tasks` | Tarefas e subtarefas (`parent_id`) |
| `mod_centralvix_pm_comments` | Comentários |
| `mod_centralvix_pm_files` | Anexos |
| `mod_centralvix_pm_milestones` | Marcos |
| `mod_centralvix_pm_time_entries` | Horas |
| `mod_centralvix_pm_logs` | Histórico |
| `mod_centralvix_pm_settings` | Configurações |

Desativação **preserva** dados.

## Dashboard admin

Projetos ativos · Atrasados · Tarefas abertas · Aguardando cliente · Concluídos · Horas registradas

## Segurança

- RBAC admin vs cliente
- CSRF em POST/AJAX
- XSS (escape em saída)
- Uploads privados (`storage/.htaccess`)
- Download autorizado (`cvix_pm_download`)
- Path traversal no resolve de arquivos
- Capsule/Query Builder (prepared statements)
- Isolamento por `client_id`

## AJAX

| Endpoint | Função |
|----------|--------|
| `task.php` | CRUD tarefas, kanban reorder |
| `comment.php` | Comentários |
| `upload.php` | Anexos |
| `status.php` | Status projeto / kanban |

## Versão

1.0.0 — CentralVix
