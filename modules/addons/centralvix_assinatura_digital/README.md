# CentralVix Assinatura Digital

Módulo WHMCS para fluxo de **aceite e assinatura eletrônica** de documentos PDF com registro de evidências técnicas e trilha de auditoria.

## Aviso legal importante

Este módulo **não afirma** possuir validade jurídica específica nem substitui:

- Certificação **ICP-Brasil**
- Assinatura digital qualificada regulamentada
- Avaliação legal do seu contrato ou processo

O sistema registra **evidências técnicas**: hash SHA-256, data/hora, IP, user-agent, aceite explícito, ID de transação e timeline auditável. A interpretação jurídica é responsabilidade do contratante e de assessoria legal.

Integração futura com provedor certificado está preparada via `CentralVixSignProviderInterface` (implementação atual: evidência interna).

## Instalação

1. Copie para `/modules/addons/centralvix_assinatura_digital/`
2. Ative em **Setup → Addon Modules**
3. Permissões admin/cliente
4. Escrita em `storage/`

## URLs

| Área | URL |
|------|-----|
| Admin | `addonmodules.php?module=centralvix_assinatura_digital` |
| Cliente | `index.php?m=centralvix_assinatura_digital` |
| Assinatura por token | `/modules/addons/centralvix_assinatura_digital/public/sign.php?t=TOKEN` |

## Fluxo

1. Admin faz upload PDF → status **Rascunho**
2. Hash SHA-256 calculado e armazenado
3. Admin envia → **Enviado** + token seguro (64+ hex, hash armazenado)
4. Cliente visualiza PDF completo no iframe → **Visualizado**
5. Checkbox obrigatório + botão **Assinar documento**
6. Evidências registradas → **Assinado**
7. Comprovante PDF gerado (`receipt.pdf`) — **original.pdf nunca alterado**
8. Cópia `signed_copy.pdf` = cópia do original (sem modificação silenciosa)

## Status

Rascunho · Enviado · Visualizado · Assinado · Recusado · Expirado · Cancelado

## Banco de dados

| Tabela | Função |
|--------|--------|
| `mod_centralvix_sign_documents` | Documentos e metadados |
| `mod_centralvix_sign_requests` | Tokens de acesso (hash, expiração) |
| `mod_centralvix_sign_events` | Timeline / auditoria |
| `mod_centralvix_sign_templates` | Templates administrativos |
| `mod_centralvix_sign_settings` | Configurações e aviso legal |

Desativação **preserva** dados e arquivos.

## Segurança

- **IDOR**: cliente só acessa `document_id` próprio; token vinculado a documento
- **Tokens**: `random_bytes(32)`, armazenados como SHA-256, expiração, revogação após uso
- **CSRF** na área logada
- **XSS**: escape em toda saída
- **Integridade**: verificação hash antes de exibir/assinar
- **Storage** protegido (`.htaccess`)
- **Download** somente via controller autorizado
- **Path traversal** bloqueado no resolve de arquivos

## Comprovante PDF

Contém: nome, documento (quando informado), data/hora, hash SHA-256, IP, ID transação, dados de auditoria e aviso de limitação jurídica.

## Assinatura desenhada

Opcional (canvas). Registrada como PNG complementar — **não é a única evidência**; o aceite checkbox + metadados permanecem obrigatórios.

## Cron

Hook `AfterCronJob` marca documentos expirados.

## Estrutura

```
centralvix_assinatura_digital/
├── centralvix_assinatura_digital.php
├── hooks.php
├── README.md
├── lib/
├── admin/
├── client/
├── ajax/
├── public/
├── storage/
├── templates/
└── assets/
```

## Versão

1.0.0 — CentralVix
