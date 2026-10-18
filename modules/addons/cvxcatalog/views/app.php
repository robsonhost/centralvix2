<?php
/** @var string $page */
/** @var string $base */
/** @var array $stats */
/** @var array $groups */
/** @var array $products */
/** @var array $emails */
/** @var array $servers */
/** @var array $modules */
/** @var array $segments */
/** @var array $packages */
/** @var array|null $edit */
/** @var object $logs */
$h = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$nav = [
    'dashboard' => 'Visão Geral',
    'install' => 'Instalar Catálogo',
    'catalog' => 'Catálogo',
    'wizard' => 'Criar em Massa',
    'groups' => 'Grupos',
    'templates' => 'Modelos',
    'emails' => 'E-mails',
    'import' => 'Importar',
    'sync' => 'Sincronização',
    'logs' => 'Logs',
    'settings' => 'Configurações',
];
?>
<link rel="stylesheet" href="../modules/addons/cvxcatalog/assets/catalog.css?v=1.2.2">
<input type="hidden" id="cvxToken" value="<?= $h($token ?? '') ?>">
</form>
<div class="cvx-app">
    <aside class="cvx-side">
        <div class="cvx-brand"><strong>CENTRALVIX</strong><span>Gerador de Catálogo</span></div>
        <nav>
            <?php foreach ($nav as $k => $label): ?>
                <a class="<?= $page === $k ? 'on' : '' ?>" href="<?= $h($base . '&cvx=' . $k) ?>"><?= $h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <main class="cvx-main">
        <?php if (!empty($flashError)): ?>
            <section class="cvx-panel"><p class="badge warn"><?= $h($flashError) ?></p></section>
        <?php endif; ?>
        <?php if (!empty($flashOk)): ?>
            <section class="cvx-panel"><p class="badge ok"><?= $h($flashOk) ?></p></section>
        <?php endif; ?>
        <?php if ($page === 'dashboard'): ?>
            <header class="cvx-head">
                <div><h1>CentralVix — Catálogo</h1><p>Produtos reais do WHMCS, prontos para o site.</p></div>
                <div class="cvx-actions">
                    <a class="btn primary" href="<?= $h($base . '&cvx=install') ?>">INSTALAR CATÁLOGO INICIAL</a>
                    <a class="btn primary" href="<?= $h($base . '&cvx=wizard') ?>">⚡ Criar em Massa</a>
                    <a class="btn" href="<?= $h($base . '&cvx=catalog&edit=1') ?>">+ Criar Produto</a>
                    <a class="btn" href="<?= $h($base . '&cvx=import') ?>">📥 Importar</a>
                    <a class="btn" href="<?= $h($base . '&cvx=ajax&action=export&format=csv') ?>">📤 Exportar CSV</a>
                    <a class="btn" href="<?= $h($base . '&cvx=catalog') ?>">Produtos</a>
                </div>
            </header>
            <section class="cvx-cards">
                <?php foreach ([['Produtos', $stats['products']], ['Grupos', $stats['groups']], ['Ativos', $stats['active']], ['Ocultos', $stats['hidden']], ['Recorrentes', $stats['recurring']], ['Sem preço', $stats['no_price']], ['Sem e-mail', $stats['no_email']], ['Sem descrição', $stats['no_desc']]] as $c): ?>
                    <article><small><?= $h($c[0]) ?></small><b><?= (int) $c[1] ?></b></article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <?php if ($page === 'install'): ?>
            <header class="cvx-head">
                <div>
                    <h1>Instalar Catálogo Inicial CentralVix</h1>
                    <p>21 produtos oficiais em 4 grupos. Preview obrigatório. Executar de novo não duplica.</p>
                </div>
            </header>
            <section class="cvx-panel">
                <p><strong>Hospedagem:</strong> Turbo 1 a X-Turbo · <strong>Revenda:</strong> REV1 a REV4 · <strong>VPS Brasil:</strong> 4 planos · <strong>VPS Canadá:</strong> 9 planos</p>
                <p class="muted">Os botões e os links abaixo gravam direto no WHMCS. Não dependem de JavaScript.</p>
                <div class="cvx-actions" style="margin-bottom:12px">
                    <a class="btn" href="<?= $h($base . '&cvx=install&cvx_act=preview') ?>">Validar / Preview</a>
                    <a class="btn primary" href="<?= $h($base . '&cvx=install&cvx_act=run&confirm=1') ?>" onclick="return confirm('Criar os produtos reais no WHMCS agora?');">Criar produtos no WHMCS</a>
                </div>
                <?php
                $previewMap = [];
                foreach (($installPreview['rows'] ?? []) as $row) {
                    $previewMap[$row['slug']] = $row;
                }
                foreach (($installResult['rows'] ?? []) as $row) {
                    $previewMap[$row['slug']] = $row;
                }
                if ($installPreview) {
                    echo '<pre class="result">Selecionados: ' . (int) $installPreview['selected'] . "\nNovos: " . (int) $installPreview['new'] . "\nJá existentes: " . (int) $installPreview['exists'] . "\nCom problema: " . (int) $installPreview['errors'] . '</pre>';
                }
                if ($installResult) {
                    echo '<pre class="result">Lote ' . $h($installResult['batch_id'] ?? '') . "\nGrupos encontrados: " . (int) ($installResult['groups_found'] ?? 0) . "\nGrupos criados: " . (int) ($installResult['groups_created'] ?? 0) . "\nAnalisados: " . (int) ($installResult['analyzed'] ?? 0) . "\nCriados: " . (int) ($installResult['created'] ?? 0) . "\nAtualizados: " . (int) ($installResult['updated'] ?? 0) . "\nIgnorados: " . (int) ($installResult['skipped'] ?? 0) . "\nErros: " . (int) ($installResult['errors'] ?? 0) . '</pre>';
                }
                ?>
                <form method="post" action="<?= $h($base . '&cvx=install') ?>" id="cvxInstallForm">
                    <input type="hidden" name="token" value="<?= $h($token ?? '') ?>">
                    <div class="cvx-actions">
                        <button class="btn" type="button" id="btnInstallSelect">Selecionar todos</button>
                        <button class="btn" type="submit" name="cvx_do" value="preview">Validar / Preview</button>
                        <button class="btn primary" type="submit" name="cvx_do" value="run" onclick="return confirm('Criar os produtos reais no WHMCS agora?');">Criar produtos no WHMCS</button>
                    </div>
                    <div class="table-wrap" style="margin-top:14px">
                        <table class="cvx-table" id="installTable">
                            <thead><tr><th></th><th>Grupo</th><th>Plano</th><th>Mensal</th><th>Anual</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach (($officialPlans ?? []) as $op):
                                $og = ($officialGroups ?? [])[$op['group_slug']] ?? [];
                                $st = $previewMap[$op['slug']] ?? null;
                                ?>
                                <tr>
                                    <td><input type="checkbox" class="inst-slug" name="slugs[]" value="<?= $h($op['slug']) ?>" checked></td>
                                    <td><?= $h($og['name'] ?? $op['group_slug']) ?></td>
                                    <td><strong><?= $h($op['name']) ?></strong></td>
                                    <td>R$ <?= $h(number_format((float) $op['monthly'], 2, ',', '.')) ?></td>
                                    <td><?= $op['annually'] ? 'R$ ' . number_format((float) $op['annually'], 2, ',', '.') : 'Pendente' ?></td>
                                    <td class="inst-status"><?= $st ? $h($st['message'] . (!empty($st['pid']) ? ' · PID ' . $st['pid'] : '')) : 'Pronto para validar' ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <?php if ($page === 'catalog' || $page === 'dashboard'): ?>
            <?php if ($page === 'catalog'): ?>
            <section class="cvx-panel">
                <p>Esta lista mostra produtos que <strong>já existem</strong> no WHMCS. Para criar os 21 oficiais, use <a class="link" href="<?= $h($base . '&cvx=install') ?>">Instalar Catálogo</a> ou <a class="link" href="<?= $h($base . '&cvx=wizard') ?>">Criar em Massa</a>.</p>
            </section>
            <?php endif; ?>
            <section class="cvx-panel">
                <form class="cvx-filters" method="get">
                    <input type="hidden" name="module" value="cvxcatalog">
                    <input type="hidden" name="cvx" value="catalog">
                    <input name="q" value="<?= $h($_GET['q'] ?? '') ?>" placeholder="Buscar nome, PID, grupo, módulo...">
                    <select name="gid">
                        <option value="">Todos os grupos</option>
                        <?php foreach ($groups as $g): ?>
                            <option value="<?= (int) $g['id'] ?>" <?= (string) ($g['id']) === (string) ($_GET['gid'] ?? '') ? 'selected' : '' ?>><?= $h($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn">Filtrar</button>
                </form>
                <table class="cvx-table">
                    <thead><tr><th>ID</th><th>Produto</th><th>Grupo</th><th>Tipo</th><th>Mensal</th><th>Anual</th><th>Módulo</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int) $p['id'] ?></td>
                            <td><strong><?= $h($p['name']) ?></strong><br><small><?= $h($p['slogan'] ?? '') ?></small></td>
                            <td><?= $h($p['group_name'] ?? '') ?></td>
                            <td><?= $h($p['type']) ?></td>
                            <td><?= (float) $p['monthly'] > 0 ? 'R$ ' . number_format((float) $p['monthly'], 2, ',', '.') : '—' ?></td>
                            <td><?= (float) $p['annually'] > 0 ? 'R$ ' . number_format((float) $p['annually'], 2, ',', '.') : '—' ?></td>
                            <td><?= $h($p['servertype'] ?: '—') ?></td>
                            <td><?= $p['hidden'] ? '<span class="badge warn">Oculto</span>' : '<span class="badge ok">Site</span>' ?></td>
                            <td class="nowr">
                                <a class="link" href="<?= $h($base . '&cvx=editor&pid=' . $p['id']) ?>">Editar</a>
                                <a class="link" href="../configproducts.php?action=edit&id=<?= (int) $p['id'] ?>" target="_blank">WHMCS</a>
                                <a class="link" href="../cart.php?a=add&pid=<?= (int) $p['id'] ?>" target="_blank">Carrinho</a>
                                <a class="link" href="/catalogo" target="_blank">Site</a>
                                <button class="link" data-dup="<?= (int) $p['id'] ?>">Duplicar</button>
                                <button class="link" data-hide="<?= (int) $p['id'] ?>">Ocultar</button>
                                <button class="link" data-hide="<?= (int) $p['id'] ?>" data-mode="retire">Descontinuar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$products): ?><tr><td colspan="9">Nenhum produto. Use Criar em Massa.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php if ($page === 'wizard'): ?>
            <?php
            $officialSlugs = ['hospedagem', 'revenda', 'vps-brasil', 'vps-canada'];
            $officialSegs = array_intersect_key($segments, array_flip($officialSlugs));
            $otherSegs = array_filter($segments, static fn($s) => !in_array($s['slug'], $officialSlugs, true));
            ?>
            <header class="cvx-head"><div><h1>Criar em Massa</h1><p>Os 4 grupos oficiais já estão prontos. Use o botão verde de cada bloco — não depende de JavaScript.</p></div></header>
            <script type="application/json" id="cvxSegments"><?= json_encode($segments, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
            <?php if (!empty($wizardResult)): ?>
                <section class="cvx-panel"><pre class="result"><?= $h(json_encode($wizardResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></pre></section>
            <?php endif; ?>
            <?php foreach ($officialSegs as $oseg):
                $bulkPlans = [];
                foreach (($oseg['catalog'] ?? []) as $wp) {
                    $bulkPlans[] = [
                        'name' => $wp['name'],
                        'monthly' => $wp['monthly'] ?? '',
                        'annually' => $wp['annually'] ?? '',
                        'quarterly' => $wp['quarterly'] ?? '',
                        'featured' => $wp['featured'] ?? 0,
                        'feature_values' => $wp['features'] ?? [],
                    ];
                }
                ?>
                <form class="cvx-panel" method="post" action="<?= $h($base . '&cvx=wizard&seg=' . $oseg['slug']) ?>">
                    <input type="hidden" name="token" value="<?= $h($token ?? '') ?>">
                    <input type="hidden" name="cvx_do" value="bulk">
                    <input type="hidden" name="segment" value="<?= $h($oseg['slug']) ?>">
                    <input type="hidden" name="group_name" value="<?= $h($oseg['name']) ?>">
                    <input type="hidden" name="type" value="<?= $h($oseg['type']) ?>">
                    <input type="hidden" name="module" value="<?= $h($oseg['module']) ?>">
                    <input type="hidden" name="welcomeemail_name" value="<?= $h($oseg['email']) ?>">
                    <input type="hidden" name="autosetup" value="payment">
                    <input type="hidden" name="on_exists" value="ignore">
                    <input type="hidden" name="plans" value="<?= $h(json_encode($bulkPlans, JSON_UNESCAPED_UNICODE)) ?>">
                    <h3><?= $h($oseg['name']) ?></h3>
                    <p><?= count($bulkPlans) ?> produtos oficiais. Se já existirem, serão ignorados (não duplica).</p>
                    <ul>
                        <?php foreach ($bulkPlans as $bp): ?>
                            <li><?= $h($bp['name']) ?> — R$ <?= $h(number_format((float) $bp['monthly'], 2, ',', '.')) ?><?= $bp['annually'] ? ' / anual R$ ' . number_format((float) $bp['annually'], 2, ',', '.') : '' ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="cvx-actions">
                        <button class="btn primary" type="submit" onclick="return confirm('Criar <?= (int) count($bulkPlans) ?> produtos de <?= $h($oseg['name']) ?> no WHMCS?');">Criar produtos no WHMCS</button>
                        <a class="btn" href="<?= $h($base . '&cvx=wizard&cvx_act=bulk&seg=' . $oseg['slug'] . '&confirm=1') ?>" onclick="return confirm('Criar <?= (int) count($bulkPlans) ?> produtos de <?= $h($oseg['name']) ?> no WHMCS?');">Criar pelo link (se o botão falhar)</a>
                    </div>
                </form>
            <?php endforeach; ?>
            <section class="cvx-wizard" id="cvxWizard" data-seg="<?= $h($wizardSeg ?? '') ?>">
                <div class="steps"><span class="on" data-s="1">1. Segmento</span><span data-s="2">2. Planos e preços</span><span data-s="3">3. Provisionamento</span><span data-s="4">4. Preview</span></div>
                <div class="step" data-step="1">
                    <p class="muted">Outros segmentos (sem catálogo oficial) — preencha os preços no assistente.</p>
                    <div class="seg-grid">
                        <?php foreach ($otherSegs as $seg): ?>
                            <a class="seg-card<?= (($wizardSeg ?? '') === $seg['slug']) ? ' on' : '' ?>" data-seg="<?= $h($seg['slug']) ?>" href="<?= $h($base . '&cvx=wizard&seg=' . $seg['slug']) ?>">
                                <b><?= $h($seg['name']) ?></b>
                                <small><?= count($seg['plans']) ?> planos modelo — preencha os preços</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="step hide" data-step="2">
                    <label>Grupo<input id="wGroup"></label>
                    <label>Quantidade de planos<input id="wQty" type="number" min="1" max="12" value="4"></label>
                    <div class="table-wrap"><table class="cvx-table" id="wPlans"><thead><tr><th>Plano</th><th>Mensal</th><th>Trimestral</th><th>Anual</th><th>Destaque</th></tr></thead><tbody></tbody></table></div>
                    <p class="muted">Catálogo oficial já vem preenchido. Você pode ajustar antes de criar. Pelo menos um preço por plano é obrigatório.</p>
                    <div id="wFeats" class="feat-grid"></div>
                    <button class="btn" type="button" id="wBack2">Voltar</button>
                    <button class="btn primary" type="button" id="wNext2">Continuar</button>
                </div>
                <div class="step hide" data-step="3">
                    <div class="grid2">
                        <label>Tipo
                            <select id="wType">
                                <option value="hostingaccount">Hospedagem</option>
                                <option value="reselleraccount">Revenda</option>
                                <option value="server">VPS / Servidor</option>
                                <option value="other">Outro</option>
                            </select>
                        </label>
                        <label>Módulo
                            <select id="wModule">
                                <option value="">Nenhum</option>
                                <?php foreach ($modules as $m): ?><option value="<?= $h($m) ?>"><?= $h($m) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                        <label>Grupo de servidores
                            <select id="wServer">
                                <option value="0">—</option>
                                <?php foreach (($serverGroups ?? []) as $sg): ?><option value="<?= (int) $sg['id'] ?>"><?= $h($sg['name']) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                        <p class="muted">Servidores detectados: <?php foreach ($servers as $s) echo $h($s['name'] . ' / ' . $s['type'] . ' · '); ?></p>
                        <label>Package cPanel
                            <input id="wPackage" list="wPkgList" placeholder="Somente packages reais">
                            <datalist id="wPkgList">
                                <?php foreach ($packages as $pk): ?><option value="<?= $h($pk) ?>"><?php endforeach; ?>
                            </datalist>
                        </label>
                        <label>E-mail de boas-vindas
                            <select id="wEmail">
                                <?php foreach ($emails as $e): ?><option value="<?= $h($e['name']) ?>"><?= $h($e['name']) ?></option><?php endforeach; ?>
                            </select>
                        </label>
                        <label>Provisionamento
                            <select id="wSetup">
                                <option value="payment">Após primeiro pagamento</option>
                                <option value="order">No pedido</option>
                                <option value="on">Após aprovação manual</option>
                                <option value="">Não automático</option>
                            </select>
                        </label>
                    </div>
                    <label>Se já existir
                        <select id="wExists">
                            <option value="ignore">Ignorar</option>
                            <option value="update">Atualizar</option>
                            <option value="duplicate">Duplicar</option>
                        </select>
                    </label>
                    <button class="btn" type="button" id="wBack3">Voltar</button>
                    <button class="btn primary" type="button" id="wNext3">Ver preview</button>
                </div>
                <div class="step hide" data-step="4">
                    <div class="preview" id="wPreview"></div>
                    <button class="btn" type="button" id="wBack4">Ajustar preços</button>
                    <div id="wCards" class="card-preview"></div>
                    <button class="btn primary" type="button" id="wCreate">Criar produtos no WHMCS</button>
                    <pre class="result hide" id="wResult"></pre>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($page === 'editor'): ?>
            <header class="cvx-head"><div><h1><?= $edit ? 'Editar produto #' . (int) $edit['id'] : 'Novo produto' ?></h1></div></header>
            <form class="cvx-editor" id="cvxEditor" method="post" action="<?= $h($base . '&cvx=editor') ?>">
                <input type="hidden" name="token" value="<?= $h($token ?? '') ?>">
                <input type="hidden" name="cvx_do" value="save_product">
                <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
                <div class="tabs">
                    <?php foreach (['Visão geral','Preços','Recursos','Provisionamento','E-mail','Domínio','Campos','SEO'] as $i => $t): ?>
                        <button type="button" class="tab <?= $i === 0 ? 'on' : '' ?>" data-tab="<?= $i ?>"><?= $t ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="tabbox on" data-tab="0">
                    <label>Nome<input name="name" value="<?= $h($edit['name'] ?? '') ?>" required></label>
                    <label>Grupo
                        <select name="gid"><?php foreach ($groups as $g): ?><option value="<?= (int) $g['id'] ?>" <?= (int) ($edit['gid'] ?? 0) === (int) $g['id'] ? 'selected' : '' ?>><?= $h($g['name']) ?></option><?php endforeach; ?></select>
                    </label>
                    <label>Slogan<input name="slogan" value="<?= $h($edit['slogan'] ?? '') ?>"></label>
                    <label>Descrição completa<textarea name="description" rows="8"><?= $h($edit['description'] ?? '') ?></textarea></label>
                    <label>Badge<input name="badge" value="<?= $h($edit['badge'] ?? '') ?>" placeholder="Mais Popular"></label>
                    <label class="chk"><input type="checkbox" name="hidden" value="1" <?= !empty($edit['hidden']) ? 'checked' : '' ?>> Oculto</label>
                    <button type="button" class="btn" id="btnGenText">Gerar texto</button>
                </div>
                <div class="tabbox" data-tab="1">
                    <label>Mensal<input name="monthly" type="number" step="0.01" value="<?= $h(($edit['monthly'] ?? 0) > 0 ? $edit['monthly'] : '') ?>"></label>
                    <label>Trimestral<input name="quarterly" type="number" step="0.01" value="<?= $h(($edit['quarterly'] ?? 0) > 0 ? $edit['quarterly'] : '') ?>"></label>
                    <label>Anual<input name="annually" type="number" step="0.01" value="<?= $h(($edit['annually'] ?? 0) > 0 ? $edit['annually'] : '') ?>"></label>
                    <label>Comparar mensal (promo real)<input name="compare_monthly" type="number" step="0.01" value="<?= $h($edit['compare_monthly'] ?? '') ?>"></label>
                </div>
                <div class="tabbox" data-tab="2">
                    <textarea name="features_json" rows="10" placeholder='[{"name":"SSD NVMe","value":"50 GB"}]'><?= $h(json_encode($edit['features'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
                </div>
                <div class="tabbox" data-tab="3">
                    <label>Tipo<select name="type">
                        <?php foreach (['hostingaccount'=>'Hospedagem','reselleraccount'=>'Revenda','server'=>'Servidor','other'=>'Outro'] as $k=>$v): ?>
                            <option value="<?= $k ?>" <?= ($edit['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select></label>
                    <label>Módulo<select name="module">
                        <option value="">Nenhum</option>
                        <?php foreach ($modules as $m): ?><option value="<?= $h($m) ?>" <?= ($edit['servertype'] ?? '') === $m ? 'selected' : '' ?>><?= $h($m) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Package<input name="package" value="<?= $h($edit['configoption1'] ?? '') ?>"></label>
                    <label>Autosetup<select name="autosetup">
                        <option value="payment">Após pagamento</option>
                        <option value="order">No pedido</option>
                        <option value="">Manual</option>
                    </select></label>
                </div>
                <div class="tabbox" data-tab="4">
                    <label>Welcome e-mail ID<input name="welcomeemail" value="<?= $h($edit['welcomeemail'] ?? '') ?>"></label>
                    <p class="muted">Templates disponíveis: <?php foreach ($emails as $e) echo $h($e['id'] . ' — ' . $e['name']) . '<br>'; ?></p>
                </div>
                <div class="tabbox" data-tab="5">
                    <label>Domínio
                        <select name="showdomainoptions">
                            <option value="0">Não utiliza</option>
                            <option value="1" <?= !empty($edit['showdomainoptions']) ? 'selected' : '' ?>>Obrigatório / opções de domínio</option>
                        </select>
                    </label>
                </div>
                <div class="tabbox" data-tab="6">
                    <p class="muted">Campos personalizados do produto no WHMCS (JSON).</p>
                    <textarea name="customfields_json" rows="8" placeholder='[{"name":"Domínio extra","type":"text","required":1}]'><?= $h(json_encode($edit['customfields'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) ?></textarea>
                </div>
                <div class="tabbox" data-tab="7">
                    <label>SEO Title<input name="seo_title" value="<?= $h($edit['seo_title'] ?? '') ?>"></label>
                    <label>SEO Description<input name="seo_description" value="<?= $h($edit['seo_description'] ?? '') ?>"></label>
                    <label>Slug<input name="slug" value="<?= $h($edit['slug'] ?? '') ?>"></label>
                </div>
                <button class="btn primary" type="submit">Salvar no WHMCS</button>
                <button class="btn" type="button" id="btnCardPreview">Visualizar card</button>
                <div id="editCard" class="card-preview"></div>
            </form>
        <?php endif; ?>

        <?php if ($page === 'groups'): ?>
            <header class="cvx-head"><div><h1>Grupos</h1></div></header>
            <form class="cvx-panel grid2" id="cvxGroup">
                <label>Nome<input name="name" required></label>
                <label>Slug<input name="slug"></label>
                <label>Título<input name="title"></label>
                <label>Subtítulo<input name="subtitle"></label>
                <label>Ícone<input name="icon" placeholder="fa-solid fa-server"></label>
                <button class="btn primary" type="submit">Salvar grupo</button>
            </form>
            <table class="cvx-table">
                <thead><tr><th>ID</th><th>Grupo</th><th>Produtos</th><th>Slug</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($groups as $g): ?>
                    <tr>
                        <td><?= (int) $g['id'] ?></td>
                        <td><?= $h($g['name']) ?></td>
                        <td><?= (int) $g['product_count'] ?></td>
                        <td><?= $h($g['slug'] ?? '') ?></td>
                        <td><button class="link" data-dup-group="<?= (int) $g['id'] ?>" data-name="<?= $h($g['name']) ?>">Duplicar grupo</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($page === 'emails'): ?>
            <header class="cvx-head"><div><h1>E-mails de produto</h1><p>Somente merge fields oficiais do WHMCS.</p></div></header>
            <table class="cvx-table">
                <thead><tr><th>ID</th><th>Nome</th><th>Assunto</th></tr></thead>
                <tbody>
                <?php foreach ($emails as $e): ?>
                    <tr><td><?= (int) $e['id'] ?></td><td><?= $h($e['name']) ?></td><td><?= $h($e['subject'] ?? '') ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if ($page === 'import'): ?>
            <header class="cvx-head"><div><h1>Importar / Exportar</h1></div></header>
            <form class="cvx-panel" id="cvxImport">
                <textarea name="json" rows="12" placeholder='{"group":{"name":"Hospedagem"},"products":[{"name":"Start","monthly":24.99}]}'></textarea>
                <label>Se existir
                    <select name="on_exists"><option value="ignore">Ignorar</option><option value="update">Atualizar</option></select>
                </label>
                <button class="btn primary">Importar JSON</button>
            </form>
            <p>
                <a class="btn" href="<?= $h($base . '&cvx=ajax&action=export&format=csv') ?>">Baixar CSV atual</a>
                <a class="btn" href="<?= $h($base . '&cvx=ajax&action=export&format=json') ?>">Baixar JSON atual</a>
                <a class="btn" href="<?= $h($base . '&cvx=ajax&action=csv_template') ?>">Baixar modelo CSV</a>
            </p>
        <?php endif; ?>

        <?php if ($page === 'templates'): ?>
            <header class="cvx-head"><div><h1>Modelos de segmentos</h1><p>Hospedagem, Revenda e VPS já têm catálogo oficial com preços.</p></div></header>
            <?php foreach ($segments as $seg): ?>
                <article class="cvx-panel">
                    <h3><?= $h($seg['name']) ?> <?php if (!empty($seg['ready'])): ?><span class="badge ok">Catálogo oficial</span><?php endif; ?></h3>
                    <p class="muted">Módulo sugerido: <?= $h($seg['module'] ?: 'nenhum') ?> · E-mail: <?= $h($seg['email']) ?></p>
                    <?php if (!empty($seg['catalog'])): ?>
                        <table class="cvx-table">
                            <thead><tr><th>Plano</th><th>Mensal</th><th>Trimestral</th><th>Anual</th></tr></thead>
                            <tbody>
                            <?php foreach ($seg['catalog'] as $p): ?>
                                <tr>
                                    <td><?= $h($p['name']) ?><?= !empty($p['featured']) ? ' · destaque' : '' ?></td>
                                    <td>R$ <?= $h(number_format((float) $p['monthly'], 2, ',', '.')) ?></td>
                                    <td>R$ <?= $h(number_format((float) $p['quarterly'], 2, ',', '.')) ?></td>
                                    <td>R$ <?= $h(number_format((float) $p['annually'], 2, ',', '.')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Planos: <?= $h(implode(', ', $seg['plans'])) ?></p>
                    <?php endif; ?>
                    <p>Recursos: <?= $h(implode(', ', $seg['features'])) ?></p>
                    <a class="btn primary" href="<?= $h($base . '&cvx=wizard') ?>">Usar no wizard</a>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($page === 'sync'): ?>
            <header class="cvx-head"><div><h1>Sincronização</h1><p>O site lê os produtos reais do WHMCS. Não há catálogo paralelo.</p></div></header>
            <section class="cvx-panel">
                <p>Ao salvar preço, grupo ou recurso neste módulo, o cache público é invalidado na hora. Não use cron para isso.</p>
                <p>URLs públicas: <code>/catalogo</code>, <code>/hospedagem</code>, <code>/vps</code>, <code>/streaming</code>, <code>/sites</code>.</p>
                <button class="btn primary" type="button" id="btnBust">Invalidar cache agora</button>
            </section>
        <?php endif; ?>

        <?php if ($page === 'settings'): ?>
            <header class="cvx-head"><div><h1>Configurações</h1><p>WHMCS <?= $h($whmcsVersion ?? '') ?> · permissões do gerador.</p></div></header>
            <section class="cvx-panel">
                <p>O acesso ao addon já é controlado em <strong>Opções → Módulos Addon</strong>. Abaixo, permissões extras por cargo.</p>
                <table class="cvx-table">
                    <thead>
                        <tr>
                            <th>Cargo</th>
                            <?php foreach ($perms as $pk => $pl): ?><th><?= $h($pl) ?></th><?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?= $h($role->name) ?></td>
                            <?php foreach ($perms as $pk => $pl): ?>
                                <td>
                                    <input type="checkbox" class="acl-box"
                                        data-role="<?= (int) $role->id ?>"
                                        data-perm="<?= $h($pk) ?>"
                                        <?= (($acl[$role->id][$pk] ?? 1) ? 'checked' : '') ?>
                                        <?= (int) $role->id === 1 ? 'disabled' : '' ?>>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
            <section class="cvx-panel">
                <h3>Reajuste de preço em massa</h3>
                <p class="muted">Informe IDs separados por vírgula. Preview no alerta antes de confirmar no servidor.</p>
                <div class="grid2">
                    <label>IDs<input id="adjIds" placeholder="2,3,4"></label>
                    <label>Percentual<input id="adjPct" type="number" step="0.1" placeholder="10"></label>
                    <label>Ciclos
                        <select id="adjCycles">
                            <option value="all">Todos</option>
                            <option value="monthly">Somente mensal</option>
                        </select>
                    </label>
                </div>
                <button class="btn primary" type="button" id="btnAdjust">Aplicar percentual</button>
            </section>
        <?php endif; ?>

        <?php if ($page === 'logs'): ?>
            <header class="cvx-head"><div><h1>Logs</h1></div></header>
            <table class="cvx-table">
                <thead><tr><th>Data</th><th>Ação</th><th>Produto</th><th>Lote</th><th>Mensagem</th></tr></thead>
                <tbody>
                <?php foreach ($logs as $l): ?>
                    <tr><td><?= $h($l->created_at) ?></td><td><?= $h($l->action) ?></td><td><?= $h($l->product_name) ?></td><td><?= $h($l->batch_id) ?></td><td><?= $h($l->message) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</div>
<script src="../modules/addons/cvxcatalog/assets/catalog.js?v=1.2.2"></script>
<form method="post" action="<?= $h($base) ?>">
<input type="hidden" name="token" value="<?= $h($token ?? '') ?>">
