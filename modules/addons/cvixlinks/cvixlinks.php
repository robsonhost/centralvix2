<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

if (!defined('ROOTDIR')) {
    define('ROOTDIR', dirname(__DIR__, 3));
}

require_once __DIR__ . '/lib/LinksService.php';
require_once __DIR__ . '/lib/WhatsAppNotify.php';

function cvixlinks_config()
{
    return [
        'name' => 'CentralVix Links de Pagamento',
        'description' => 'Gera links personalizados para o cliente gerar a própria fatura (data e valor) ou para um novo cliente se cadastrar com mensalidade e vencimento pré-definidos.',
        'version' => '1.0.0',
        'author' => 'CentralVix',
        'language' => 'portuguese-br',
        'fields' => [
            'default_validity_days' => [
                'FriendlyName' => 'Validade padrão (dias)',
                'Type' => 'text',
                'Size' => '5',
                'Default' => '30',
                'Description' => 'Validade dos links ao serem criados.',
            ],
        ],
    ];
}

function cvixlinks_activate()
{
    try {
        CvixLinksService::ensureTable();
        return ['status' => 'success'];
    } catch (Throwable $e) {
        return ['status' => 'error', 'description' => 'Falha ao criar tabela mod_cvixlinks: ' . $e->getMessage()];
    }
}

function cvixlinks_deactivate()
{
    return ['status' => 'success'];
}

function cvixlinks_output($vars)
{
    $settings = [];
    foreach (WHMCS\Database\Capsule::table('tbladdonmodules')->where('module', 'cvixlinks')->get() as $row) {
        $settings[$row->setting] = $row->value;
    }
    $defaultValidity = max(1, (int) ($settings['default_validity_days'] ?? 30));

    $message = '';
    $messageType = 'success';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['cvixlinks_action'] ?? '') !== '') {
        $csrfOk = !empty($_SESSION['cvixlinks_tk']) && hash_equals((string) $_SESSION['cvixlinks_tk'], (string) ($_POST['cvixlinks_tk'] ?? ''));
        if (!$csrfOk) {
            $message = 'Token de segurança inválido. Recarregue a página e tente novamente.';
            $messageType = 'danger';
        } elseif ($_POST['cvixlinks_action'] === 'create') {
            [$message, $messageType] = cvixlinks_handle_create($defaultValidity);
        } elseif ($_POST['cvixlinks_action'] === 'revoke') {
            $id = (int) ($_POST['id'] ?? 0);
            $updated = WHMCS\Database\Capsule::table(CvixLinksService::TABLE)
                ->where('id', $id)->where('status', 'active')
                ->update(['status' => 'revoked', 'used_at' => date('Y-m-d H:i:s')]);
            $message = $updated ? 'Link revogado.' : 'Link não encontrado ou já utilizado/revogado.';
            $messageType = $updated ? 'success' : 'warning';
        }
    }

    if (empty($_SESSION['cvixlinks_tk'])) {
        $_SESSION['cvixlinks_tk'] = bin2hex(random_bytes(32));
    }
    $csrf = $_SESSION['cvixlinks_tk'];

    $rows = WHMCS\Database\Capsule::table(CvixLinksService::TABLE)->orderBy('id', 'desc')->limit(200)->get();
    $systemUrl = rtrim((string) WHMCS\Database\Capsule::table('tblconfiguration')->where('setting', 'SystemURL')->value('value'), '/');

    cvixlinks_render_admin($csrf, $rows, $systemUrl, $message, $messageType, $defaultValidity);
}

function cvixlinks_handle_create($defaultValidity)
{
    $type = $_POST['type'] === 'signup' ? 'signup' : 'invoice';
    $description = CvixLinksService::sanitizeText($_POST['description'] ?? '', 250);
    $validity = (int) ($_POST['validity_days'] ?? $defaultValidity);
    if ($validity < 1 || $validity > 365) {
        $validity = $defaultValidity;
    }

    $clientId = null;
    $amount = null;
    $fixedAmount = 0;
    $dueDay = null;

    if ($type === 'invoice') {
        $clientInput = trim((string) ($_POST['client'] ?? ''));
        $client = CvixLinksService::resolveClient($clientInput);
        if (!$client) {
            return ['Cliente não encontrado. Informe o ID ou o e-mail cadastrado.', 'danger'];
        }
        $clientId = (int) $client->id;
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        if ($amountRaw !== '') {
            $amount = CvixLinksService::parseAmount($amountRaw);
            if ($amount === null) {
                return ['Valor inválido. Use o formato 150,00.', 'danger'];
            }
            $fixedAmount = 1;
        }
    } else {
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        $amount = CvixLinksService::parseAmount($amountRaw);
        if ($amount === null) {
            return ['Para convite de cadastro, informe o valor da mensalidade (ex.: 59,90).', 'danger'];
        }
        $dueDay = (int) ($_POST['due_day'] ?? 0);
        if ($dueDay < 1 || $dueDay > 28) {
            return ['Escolha o melhor dia de vencimento (entre 1 e 28).', 'danger'];
        }
    }

    $row = CvixLinksService::create([
        'type' => $type,
        'client_id' => $clientId,
        'amount' => $amount,
        'fixed_amount' => $fixedAmount,
        'due_day' => $dueDay,
        'description' => $description,
        'expires_at' => date('Y-m-d', strtotime('+' . $validity . ' days')),
        'created_by' => (int) ($_SESSION['adminid'] ?? 0),
    ]);

    $link = CvixLinksService::publicUrl($row);
    return ['Link criado com sucesso: ' . $link, 'success'];
}

function cvixlinks_render_admin($csrf, $rows, $systemUrl, $message, $messageType, $defaultValidity)
{
    $e = static function ($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };
    $statusLabels = [
        'active' => '<span class="label label-success">Ativo</span>',
        'used' => '<span class="label label-default">Usado</span>',
        'revoked' => '<span class="label label-danger">Revogado</span>',
    ];
    ?>
    <div style="max-width:1100px;margin:0 auto;">
        <h2>CentralVix — Links de Pagamento e Cadastro</h2>
        <p class="text-muted">Crie links personalizados para enviar aos clientes pelo WhatsApp. <strong>Fatura avulsa:</strong> o cliente escolhe a data de vencimento e informa o valor. <strong>Convite de cadastro:</strong> o novo cliente se cadastra com mensalidade e dia de vencimento já definidos.</p>

        <?php if ($message !== '') { ?>
            <div class="alert alert-<?php echo $e($messageType); ?>"><?php echo $e($message); ?></div>
        <?php } ?>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Criar novo link</strong></div>
            <div class="panel-body">
                <form method="post" class="form-horizontal">
                    <input type="hidden" name="cvixlinks_action" value="create">
                    <input type="hidden" name="cvixlinks_tk" value="<?php echo $e($csrf); ?>">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Tipo de link</label>
                        <div class="col-sm-6">
                            <select name="type" id="cvixlinks-type" class="form-control">
                                <option value="invoice">Fatura avulsa (cliente existente)</option>
                                <option value="signup">Convite de cadastro (novo cliente)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group" id="cvixlinks-client-row">
                        <label class="col-sm-3 control-label">Cliente (ID ou e-mail)</label>
                        <div class="col-sm-6">
                            <input type="text" name="client" class="form-control" placeholder="Ex.: 123 ou cliente@email.com">
                            <p class="text-muted" style="margin:6px 0 0;">O link só funciona para este cliente e só gera 1 fatura.</p>
                        </div>
                    </div>
                    <div class="form-group" id="cvixlinks-amount-row">
                        <label class="col-sm-3 control-label">Valor (R$)</label>
                        <div class="col-sm-3">
                            <input type="text" name="amount" class="form-control" placeholder="59,90">
                            <p class="text-muted" style="margin:6px 0 0;" id="cvixlinks-amount-hint">Deixe vazio para o cliente informar o valor.</p>
                        </div>
                    </div>
                    <div class="form-group" id="cvixlinks-dueday-row" style="display:none;">
                        <label class="col-sm-3 control-label">Melhor dia de vencimento</label>
                        <div class="col-sm-3">
                            <select name="due_day" class="form-control">
                                <?php for ($d = 1; $d <= 28; $d++) { ?>
                                    <option value="<?php echo $d; ?>" <?php echo $d === 10 ? 'selected' : ''; ?>>Todo dia <?php echo $d; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Descrição (opcional)</label>
                        <div class="col-sm-6">
                            <input type="text" name="description" class="form-control" maxlength="250" placeholder="Ex.: Mensalidade hospedagem - Cliente João">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Validade (dias)</label>
                        <div class="col-sm-2">
                            <input type="number" name="validity_days" class="form-control" min="1" max="365" value="<?php echo (int) $defaultValidity; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-6">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-link"></i> Gerar link</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel panel-default">
            <div class="panel-heading"><strong>Links gerados</strong> (últimos 200)</div>
            <div class="panel-body table-responsive">
                <table class="table table-striped table-condensed">
                    <thead>
                        <tr>
                            <th>#</th><th>Tipo</th><th>Cliente</th><th>Valor</th><th>Descrição</th>
                            <th>Status</th><th>Validade</th><th>Criado em</th><th>Usado em</th><th width="230">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($rows) === 0) { ?>
                        <tr><td colspan="10" class="text-center text-muted">Nenhum link criado ainda.</td></tr>
                    <?php } ?>
                    <?php foreach ($rows as $row) {
                        $clientName = '';
                        if (!empty($row->client_id)) {
                            $c = CvixLinksService::resolveClient((string) $row->client_id);
                            $clientName = $c ? ('#' . $c->id . ' ' . $c->firstname . ' ' . $c->lastname) : ('#' . $row->client_id);
                        }
                        $link = CvixLinksService::publicUrl($row);
                        $shareText = $row->type === 'signup'
                            ? 'Olá! Aqui é da CENTRALVIX. Crie sua conta pelo link abaixo — sua mensalidade já fica pré-configurada conforme combinado: ' . $link
                            : 'Olá! Aqui é da CENTRALVIX. Acesse seu link e gere sua fatura com a data de vencimento que preferir: ' . $link;
                        ?>
                        <tr>
                            <td><?php echo (int) $row->id; ?></td>
                            <td><?php echo $row->type === 'signup' ? '<span class="label label-info">Cadastro</span>' : '<span class="label label-primary">Fatura</span>'; ?></td>
                            <td><?php echo $e($clientName); ?></td>
                            <td><?php echo $row->amount !== null ? 'R$ ' . $e(CvixLinksService::formatMoney((float) $row->amount)) : '<span class="text-muted">cliente define</span>'; ?></td>
                            <td><?php echo $e($row->description); ?></td>
                            <td><?php echo $statusLabels[$row->status] ?? $e($row->status); ?></td>
                            <td><?php echo $e($row->expires_at); ?></td>
                            <td><?php echo $e($row->created_at); ?></td>
                            <td><?php echo $e($row->used_at ?: '—'); ?></td>
                            <td>
                                <div class="btn-group btn-group-xs" role="group">
                                    <button type="button" class="btn btn-default" onclick="cvixlinksCopy('<?php echo $e($link); ?>')" title="Copiar link"><i class="fa fa-copy"></i></button>
                                    <a class="btn btn-success" target="_blank" rel="noopener" href="https://wa.me/?text=<?php echo rawurlencode($shareText); ?>" title="Enviar pelo WhatsApp"><i class="fa fa-whatsapp"></i></a>
                                    <?php if ($row->status === 'active') { ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Revogar este link?');">
                                        <input type="hidden" name="cvixlinks_action" value="revoke">
                                        <input type="hidden" name="cvixlinks_tk" value="<?php echo $e($csrf); ?>">
                                        <input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
                                        <button type="submit" class="btn btn-danger" title="Revogar"><i class="fa fa-ban"></i></button>
                                    </form>
                                    <?php } ?>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
    function cvixlinksCopy(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () { alert('Link copiado!'); });
        } else {
            window.prompt('Copie o link:', text);
        }
    }
    (function () {
        var typeSel = document.getElementById('cvixlinks-type');
        var clientRow = document.getElementById('cvixlinks-client-row');
        var amountRow = document.getElementById('cvixlinks-amount-row');
        var amountHint = document.getElementById('cvixlinks-amount-hint');
        var dueDayRow = document.getElementById('cvixlinks-dueday-row');
        function sync() {
            var isSignup = typeSel.value === 'signup';
            clientRow.style.display = isSignup ? 'none' : '';
            dueDayRow.style.display = isSignup ? '' : 'none';
            amountHint.textContent = isSignup
                ? 'Mensalidade que será cobrada no cadastro.'
                : 'Deixe vazio para o cliente informar o valor.';
        }
        typeSel.addEventListener('change', sync);
        sync();
    })();
    </script>
    <?php
}
