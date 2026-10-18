# CentralVix Client

Template premium da **área do cliente** WHMCS — hospedagem, cloud, VPS e streaming.

## Identidade visual

- Paleta azul CentralVix (`#071426` → `#00C8FF`)
- Sidebar + topbar (desktop)
- Bottom navigation (mobile, safe-area)
- Dark mode: claro / escuro / sistema
- Login dividido, dashboard animado, design system `.cv-*`

## Estrutura

```
templates/centralvix-client/
├── assets/css/          # Tema + animations.css
├── assets/js/           # centralvix-client.min.js
├── assets/img/          # Logos SVG + favicon
├── includes/            # head, sidebar, topbar, bottom-nav
├── header.tpl / footer.tpl
├── clientareahome.tpl
├── login.tpl
└── theme.yaml
```

## Compatibilidade

Baseado em **Twenty-One**. Checkout, Mercado Pago, Asaas, Pix e custom fields preservados.

Ver `INSTALL.md` e `ANALISE-INSTALACAO.md`.
