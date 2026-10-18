# Cliente PHP — CentralVix Atualizador

Biblioteca standalone para integrar atualizações em sistemas distribuídos.

## Arquivos

| Arquivo | Função |
|---------|--------|
| `CentralVixUpdaterClient.php` | Cliente API + instalador com backup/rollback |
| `run_update.php` | Script de demonstração do fluxo completo |

## Fluxo recomendado

1. **Consultar** — `checkUpdate()`
2. **Baixar** — `downloadPackage()` com token temporário
3. **Validar** — `verifySha256()` contra checksum da API
4. **Backup** — `CentralVixUpdateInstaller::createBackup()`
5. **Manutenção** — `enableMaintenance()`
6. **Atualizar** — `applyZipUpdate()` (merge com proteção ZIP Slip)
7. **Rollback** — automático em erro via backup

## Segurança

- **DRY_RUN** é o padrão em `run_update.php` — não altera produção até `CVIX_APPLY=1`
- Verificação de assinatura da resposta JSON com `license_key` (sem segredo do servidor)
- Tokens de download expiram; não há URL permanente para pacotes
- Nunca confie em checksum enviado pelo cliente — sempre valide o arquivo baixado

## Variáveis de ambiente

```bash
export CVIX_API_BASE="https://centralvix.com.br/modules/addons/centralvix_updater/api"
export CVIX_PRODUCT="meu-sistema"
export CVIX_LICENSE="CVIX-XXXXXX-..."
export CVIX_VERSION="1.0.0"
export CVIX_TARGET="/caminho/do/sistema"
export CVIX_APPLY=1   # omitir ou 0 para dry-run
php run_update.php
```
