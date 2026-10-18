# CentralVix Client — Instalação

## Requisitos

- WHMCS 8.x com template **Twenty-One** em `templates/twenty-one/`
- PHP 7.2+ (recomendado 8.1+)
- Permissão de escrita em `templates/` e `includes/hooks/`

## Instalação automática

Na raiz WHMCS (onde existe `configuration.php`):

```powershell
cd C:\caminho\para\whmcs
Copy-Item -Recurse J:\Sistemas\WHMCS\templates\centralvix-client .\templates\centralvix-client-overlay -Force
Copy-Item J:\Sistemas\WHMCS\includes\hooks\centralvix_client_theme.php .\includes\hooks\ -Force
.\scripts\install-centralvix-client.ps1 -WhmcsRoot (Get-Location) -OverlayPath ".\templates\centralvix-client-overlay"
```

Ou, se o pacote já estiver em `templates/centralvix-client/` **antes** de rodar o script (somente overlay, sem twenty-one completo):

```powershell
.\scripts\install-centralvix-client.ps1 -WhmcsRoot "C:\caminho\whmcs"
```

O script:

1. Faz backup em `backup-centralvix-client/`
2. Copia `twenty-one` → `centralvix-client`
3. Sobrescreve com arquivos CentralVix
4. Instala o hook

## Ativar template

**Setup → General Settings → Template → centralvix-client**

## WhatsApp (opcional)

Em `configuration.php`:

```php
define('CENTRALVIX_CLIENT_WHATSAPP', '5511999999999');
```

## Design system (addons)

Classes reutilizáveis: `.cv-card`, `.cv-stat-card`, `.cv-button`, `.cv-badge`, `.cv-table`, `.cv-service-card`, `.cv-status`, `.cv-page-header`, `.cv-stream-panel`, `.cv-vps-panel`, `.cv-hosting-panel`.

## Rollback

Restaure a pasta do backup `backup-centralvix-client/YYYYMMDD-HHMMSS/centralvix-client-prev`.

## Não modificar

- `/templates/twenty-one/`
- `/templates/six/`
- Core WHMCS
- Order forms (salvo estilização global via CSS)
