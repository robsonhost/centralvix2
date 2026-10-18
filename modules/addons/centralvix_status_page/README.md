# CentralVix Status Page

Addon profissional para WHMCS que oferece uma **central de status pública** e **monitoramento automatizado** dos serviços da CentralVix.

## Requisitos

| Componente | Versão |
|------------|--------|
| WHMCS | 7.x / 8.x |
| PHP | 7.4+ (recomendado 8.2+) |
| Extensões | `curl`, `json`, `pdo_mysql` |
| Opcional | `exec` (para monitor PING) |

## Instalação

1. Copie a pasta `centralvix_status_page` para:

   ```
   /modules/addons/centralvix_status_page/
   ```

2. No WHMCS Admin: **Configurações → Addon Modules**

3. Ative **CentralVix Status Page** e clique em **Save Changes**

4. Clique em **Configure** e defina:
   - E-mail de alertas
   - WhatsApp (endpoint, token, instância, telefone)
   - Intervalo de checagem (minutos)
   - Cooldown de notificações (anti-spam)

5. Acesse o módulo em **Addons → CentralVix Status Page**

## Página pública

URL padrão (relativa à raiz do WHMCS):

```
https://seudominio.com.br/modules/addons/centralvix_status_page/public/status.php
```

Detalhe de incidente:

```
https://seudominio.com.br/modules/addons/centralvix_status_page/public/incident.php?id=1
```

A página **não exibe** IPs internos, credenciais ou dados sensíveis — apenas nome, descrição, status e métricas públicas.

## Monitoramento (Cron)

O módulo registra o hook `AfterCronJob` do WHMCS e executa checagens automaticamente respeitando o **Intervalo de checagem** configurado.

### Cron do WHMCS (recomendado)

Configure o cron padrão do WHMCS (a cada 5 minutos):

```bash
*/5 * * * * php -q /caminho/para/whmcs/crons/cron.php
```

### Cron manual dedicado (alternativa)

```bash
*/5 * * * * php -q /caminho/para/whmcs/modules/addons/centralvix_status_page/cron.php
```

### Execução manual via CLI

```bash
php /caminho/para/whmcs/modules/addons/centralvix_status_page/cron.php
```

## Tipos de monitor

| Tipo | Descrição |
|------|-----------|
| HTTP / HTTPS | Status code, tempo de resposta, SSL |
| TCP / Porta | Conexão em host:porta |
| PING | ICMP (requer `exec`) |
| Icecast / Shoutcast | Stream online/offline |
| API JSON | Valida resposta JSON |
| URL personalizada | HTTP genérico |

## Status disponíveis

Operacional · Degradação de desempenho · Instabilidade · Interrupção parcial · Interrupção total · Manutenção programada · Investigando · Monitorando · Resolvido

## Notificações

Eventos suportados:

- Incidente criado
- Serviço offline
- Serviço restaurado
- Manutenção programada / iniciada / concluída

Canais: **E-mail** e **WhatsApp** (via API configurável).

O sistema possui **cooldown anti-spam** — não envia alertas repetidos a cada verificação.

Tokens WhatsApp **nunca** são registrados em logs.

## Segurança

- CSRF em todas requisições AJAX administrativas
- Validação de sessão admin
- Prepared statements via `WHMCS\Database\Capsule`
- Escape HTML e sanitização de entrada
- Proteção **SSRF**: bloqueia `localhost`, IPs privados, `169.254.x.x` e metadata
- Opção explícita `Permitir IPs privados nos monitores` (desabilitada por padrão)

## Estrutura de arquivos

```
centralvix_status_page/
├── centralvix_status_page.php   # Entrada do addon
├── hooks.php                    # AJAX admin + cron AfterCronJob
├── cron.php                     # Execução manual
├── README.md
├── lib/                         # Serviços (Status, Monitor, Incident, etc.)
├── admin/                       # Painéis administrativos
├── public/                      # Páginas públicas
├── ajax/                        # Endpoints AJAX
├── templates/                   # Layouts PHP
├── assets/css|js/               # Estilos e scripts
├── lang/portuguese-br.php       # Traduções
└── storage/logs/                # Logs (protegido)
```

## Tabelas do banco

- `mod_centralvix_status_services`
- `mod_centralvix_status_checks`
- `mod_centralvix_status_incidents`
- `mod_centralvix_status_incident_updates`
- `mod_centralvix_status_maintenance`
- `mod_centralvix_status_subscribers`
- `mod_centralvix_status_settings`
- `mod_centralvix_status_history`

**Desativação:** os dados **não são removidos** (sem DROP TABLE).

## Permissões

Após upload no servidor:

```bash
chmod 775 modules/addons/centralvix_status_page/storage
chmod 775 modules/addons/centralvix_status_page/storage/logs
```

Se alterar `hooks.php`, desative e reative o addon para recarregar hooks.

## Suporte

Desenvolvido para **CentralVix** — integração nativa com WHMCS Capsule e hooks oficiais.
