# CentralVix Migração de Hospedagem

Central de solicitações de migração de hospedagem para clientes CentralVix. **Gerenciador de processo** — execução manual pela equipe, com interfaces preparadas para automações futuras.

## Instalação

1. Copie para `/modules/addons/centralvix_migracao_hospedagem/`
2. Ative em **Setup → Addon Modules**
3. Configure notificações e permissões de acesso
4. Garanta escrita em `storage/`

Desativação **preserva** solicitações, checklist e histórico.

## URLs

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_migracao_hospedagem` |
| Cliente | `index.php?m=centralvix_migracao_hospedagem` |

## Fluxo

1. Cliente solicita migração  
2. Equipe analisa  
3. Agendamento  
4. Backup → arquivos → banco → e-mails  
5. Testes → DNS → monitoramento  
6. Conclusão  

## Status

Solicitada · Em análise · Aguardando cliente · Agendada · Backup · Migrando arquivos · Migrando banco · Migrando e-mails · Testando · Aguardando DNS · Monitorando · Concluída · Falhou · Cancelada

## Painéis suportados (origem)

cPanel · Plesk · DirectAdmin · CyberPanel · Outro

## Checklist interno

Backup origem · Arquivos · Bancos · Cron · SSL · E-mails · DNS · Permissões · PHP · Teste site · Teste formulário · Teste e-mail · Finalizado

## Credenciais

- Armazenamento **AES-256-GCM** (`CredentialVault`)
- Senha **nunca** em logs ou WhatsApp
- Exibição mascarada (`********`) após cadastro
- Purga automática ao concluir/cancelar (configurável)
- Purga manual pelo admin

## Notificações

| Evento | Canal |
|--------|--------|
| Solicitação recebida | E-mail + WhatsApp (preparado) |
| Informação necessária | E-mail + WhatsApp |
| Migração agendada | E-mail + WhatsApp |
| Migração iniciada | E-mail + WhatsApp |
| Alteração DNS | E-mail + WhatsApp |
| Concluída | E-mail + WhatsApp |

WhatsApp: apenas preparação de payload em log (`notify_whatsapp_prepared`) — integração externa não incluída nesta versão.

## Uploads

Cliente pode anexar screenshots, documentação e backup (se habilitado). Arquivos em `storage/uploads/` — acesso via download autenticado (IDOR protegido).

Tipos: JPEG, PNG, GIF, WebP, PDF, TXT, ZIP.

## Segurança

| Medida | Implementação |
|--------|----------------|
| CSRF | Admin e cliente |
| IDOR | Ownership em requests/files |
| XSS | Escape + strip tags |
| Credenciais | Criptografia, redaction em logs |
| Sessão admin | Endpoints AJAX validados |
| Uploads | MIME, tamanho, storage privado |

## Providers (automação futura)

Interface `CentralVixMigMigrationProviderInterface` — implementação padrão `ManualMigrationProvider` (sem SSH/cPanel automático).

Configure `migration_provider` em settings DB para extensões futuras.

## Banco de dados

| Tabela | Função |
|--------|--------|
| `mod_centralvix_migration_requests` | Solicitações |
| `mod_centralvix_migration_checklist` | Itens por solicitação |
| `mod_centralvix_migration_comments` | Comunicação |
| `mod_centralvix_migration_files` | Anexos |
| `mod_centralvix_migration_logs` | Auditoria (sem senhas) |
| `mod_centralvix_migration_settings` | Config e chave vault |

## Área do cliente

Timeline profissional sem exposição de infraestrutura interna. Progresso baseado no checklist concluído pela equipe.

## Admin

Dashboard com contadores: abertas, em andamento, aguardando cliente, agendadas, concluídas, problemas.
