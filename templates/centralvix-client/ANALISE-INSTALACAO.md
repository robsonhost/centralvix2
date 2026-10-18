# CentralVix Client — Análise de instalação

## Ambiente detectado (workspace local)

| Item | Resultado |
|------|-----------|
| WHMCS completo | **Não presente** neste repositório (`configuration.php`, `init.php` ausentes) |
| PHP CLI | 8.2.x (verificar no servidor com `php -v`) |
| Template base oficial | **Twenty-One** (WHMCS 8.x) |
| Bootstrap | 4.5.3 (via `theme.yaml` Twenty-One) |
| jQuery | 1.12.4 |
| Font Awesome | 5.10.1 |
| Smarty | Integrado ao WHMCS (`.tpl`) |

## Estrutura correta

```
/templates/centralvix-client/
```

Nome com hífen é permitido na **área do cliente**. O instalador copia `templates/twenty-one/` e aplica o overlay CentralVix para manter **100% dos `.tpl` oficiais** + compatibilidade com order forms, gateways e addons.

## Arquivos customizados (overlay)

- `theme.yaml`, `header.tpl`, `footer.tpl`
- `clientareahome.tpl`, `login.tpl`, `clientregister.tpl`
- `clientareaproducts.tpl`, `clientareainvoices.tpl`, `supportticketslist.tpl`, `clientareadetails.tpl`
- `includes/head.tpl`, `includes/centralvix-*.tpl`
- `assets/css/`, `assets/js/`, `assets/img/`
- `manifest.webmanifest` (PWA ready, opcional)

## Checkout / Order Form

Quando `$inShoppingCart` é verdadeiro, o layout Twenty-One do carrinho é preservado (`cv-checkout`). **Não altere** order forms em `/templates/orderforms/` neste pacote.

## Hook

`includes/hooks/centralvix_client_theme.php`

WhatsApp opcional — em `configuration.php`:

```php
define('CENTRALVIX_CLIENT_WHATSAPP', '5511999999999');
```

## Próximo passo no servidor

```powershell
.\scripts\detect-whmcs.ps1 -WhmcsRoot "C:\caminho\whmcs"
.\scripts\install-centralvix-client.ps1 -WhmcsRoot "C:\caminho\whmcs"
```

Ativar em **Setup → General Settings → Template → centralvix-client**.
