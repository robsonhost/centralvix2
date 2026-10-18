<?php
/* Smarty version 4.5.3, created on 2026-09-05 23:15:15
  from '/home/centralvixcom/public_html/templates/centralvix-client/clientareahome.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9cccb3298c93_38708867',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '45ba0e6c52c200c0021cb7f48932adca1fac4dab' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/centralvix-client/clientareahome.tpl',
      1 => 1786330143,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a9cccb3298c93_38708867 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->smarty->ext->_tplFunction->registerTplFunctions($_smarty_tpl, array (
  'outputHomePanels' => 
  array (
    'compiled_filepath' => '/home/centralvixcom/public_html/templates_c/45ba0e6c52c200c0021cb7f48932adca1fac4dab_0.file.clientareahome.tpl.php',
    'uid' => '45ba0e6c52c200c0021cb7f48932adca1fac4dab',
    'call_name' => 'smarty_template_function_outputHomePanels_21079845846a9cccb325c0e3_31951544',
  ),
));
$_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/flashmessage.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

<div class="cv-dashboard cv-fade-in">
    <div class="cv-hero cv-slide-up">
        <div class="cv-hero-bg" aria-hidden="true">
            <span class="cv-blob cv-blob-1"></span>
            <span class="cv-blob cv-blob-2"></span>
        </div>
        <div class="cv-hero-content">
            <p class="cv-hero-eyebrow cv-fade-in">Olá, <strong><?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/centralvix-client-name.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></strong></p>
            <h1 class="cv-hero-title cv-slide-up">Bem-vindo à sua CentralVix.</h1>
            <p class="cv-hero-subtitle cv-slide-up">Gerencie seus serviços, pagamentos e suporte em um só lugar.</p>
        </div>
    </div>

    <div class="cv-stats-grid cv-stagger">
        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=services" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-server"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['productsnumactive'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['productsnumactive'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'navservices'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>

        <?php if ($_smarty_tpl->tpl_vars['clientsstats']->value['numdomains'] || $_smarty_tpl->tpl_vars['registerdomainenabled']->value || $_smarty_tpl->tpl_vars['transferdomainenabled']->value) {?>
        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=domains" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-globe"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numactivedomains'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numactivedomains'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'navdomains'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>
        <?php } elseif ($_smarty_tpl->tpl_vars['condlinks']->value['affiliates'] && $_smarty_tpl->tpl_vars['clientsstats']->value['isAffiliate']) {?>
        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/affiliates.php" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-handshake"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numaffiliatesignups'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numaffiliatesignups'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'affiliatessignups'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>
        <?php } else { ?>
        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=quotes" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-file-alt"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numquotes'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numquotes'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'quotes'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>
        <?php }?>

        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/supporttickets.php" class="cv-stat-card cv-card-interactive">
            <div class="cv-stat-icon"><i class="fas fa-life-ring"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numactivetickets'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numactivetickets'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'navtickets'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>

        <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=invoices" class="cv-stat-card cv-card-interactive cv-stat-card-accent">
            <div class="cv-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="cv-stat-body">
                <span class="cv-stat-value" data-cv-counter="<?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numunpaidinvoices'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
"><?php echo (($tmp = $_smarty_tpl->tpl_vars['clientsstats']->value['numunpaidinvoices'] ?? null)===null||$tmp==='' ? 0 ?? null : $tmp);?>
</span>
                <span class="cv-stat-label"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'navinvoices'),$_smarty_tpl ) );?>
</span>
            </div>
        </a>
    </div>

    <div class="cv-section cv-slide-up">
        <div class="cv-page-header">
            <h2 class="cv-page-header-title">Ações rápidas</h2>
        </div>
        <div class="cv-quick-actions">
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/cart.php" class="cv-quick-action">
                <i class="fas fa-plus-circle"></i>
                <span>Contratar Serviço</span>
            </a>
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=services" class="cv-quick-action">
                <i class="fas fa-server"></i>
                <span>Ver Serviços</span>
            </a>
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=invoices" class="cv-quick-action">
                <i class="fas fa-credit-card"></i>
                <span>Pagar Fatura</span>
            </a>
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/submitticket.php" class="cv-quick-action">
                <i class="fas fa-ticket-alt"></i>
                <span>Abrir Ticket</span>
            </a>
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=domains" class="cv-quick-action">
                <i class="fas fa-globe"></i>
                <span>Meus Domínios</span>
            </a>
            <a href="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/clientarea.php?action=downloads" class="cv-quick-action">
                <i class="fas fa-download"></i>
                <span>Downloads</span>
            </a>
        </div>
    </div>

    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['addons_html']->value, 'addon_html');
$_smarty_tpl->tpl_vars['addon_html']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['addon_html']->value) {
$_smarty_tpl->tpl_vars['addon_html']->do_else = false;
?>
        <div class="cv-addon-panel cv-slide-up"><?php echo $_smarty_tpl->tpl_vars['addon_html']->value;?>
</div>
    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>

    <?php if ($_smarty_tpl->tpl_vars['captchaError']->value) {?>
        <div class="cv-alert cv-alert-danger cv-shake-once"><?php echo $_smarty_tpl->tpl_vars['captchaError']->value;?>
</div>
    <?php }?>

    

    <div class="cv-panels-grid row">
        <div class="col-12">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['panels']->value, 'item');
$_smarty_tpl->tpl_vars['item']->iteration = 0;
$_smarty_tpl->tpl_vars['item']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->do_else = false;
$_smarty_tpl->tpl_vars['item']->iteration++;
$__foreach_item_4_saved = $_smarty_tpl->tpl_vars['item'];
?>
                <?php if ($_smarty_tpl->tpl_vars['item']->value->getExtra('colspan')) {?>
                    <?php $_smarty_tpl->smarty->ext->_tplFunction->callTemplateFunction($_smarty_tpl, 'outputHomePanels', array(), true);?>

                    <?php $_smarty_tpl->_assignInScope('panels', $_smarty_tpl->tpl_vars['panels']->value->removeChild($_smarty_tpl->tpl_vars['item']->value->getName()));?>
                <?php }?>
            <?php
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_4_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </div>
        <div class="col-md-6 col-lg-12 col-xl-6 cv-panels-col">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['panels']->value, 'item');
$_smarty_tpl->tpl_vars['item']->iteration = 0;
$_smarty_tpl->tpl_vars['item']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->do_else = false;
$_smarty_tpl->tpl_vars['item']->iteration++;
$__foreach_item_5_saved = $_smarty_tpl->tpl_vars['item'];
?>
                <?php if ((1 & $_smarty_tpl->tpl_vars['item']->iteration)) {?>
                    <?php $_smarty_tpl->smarty->ext->_tplFunction->callTemplateFunction($_smarty_tpl, 'outputHomePanels', array(), true);?>

                <?php }?>
            <?php
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_5_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </div>
        <div class="col-md-6 col-lg-12 col-xl-6 cv-panels-col">
            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['panels']->value, 'item');
$_smarty_tpl->tpl_vars['item']->iteration = 0;
$_smarty_tpl->tpl_vars['item']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->do_else = false;
$_smarty_tpl->tpl_vars['item']->iteration++;
$__foreach_item_6_saved = $_smarty_tpl->tpl_vars['item'];
?>
                <?php if (!(1 & $_smarty_tpl->tpl_vars['item']->iteration)) {?>
                    <?php $_smarty_tpl->smarty->ext->_tplFunction->callTemplateFunction($_smarty_tpl, 'outputHomePanels', array(), true);?>

                <?php }?>
            <?php
$_smarty_tpl->tpl_vars['item'] = $__foreach_item_6_saved;
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
        </div>
    </div>
</div>
<?php }
/* smarty_template_function_outputHomePanels_21079845846a9cccb325c0e3_31951544 */
if (!function_exists('smarty_template_function_outputHomePanels_21079845846a9cccb325c0e3_31951544')) {
function smarty_template_function_outputHomePanels_21079845846a9cccb325c0e3_31951544(Smarty_Internal_Template $_smarty_tpl,$params) {
foreach ($params as $key => $value) {
$_smarty_tpl->tpl_vars[$key] = new Smarty_Variable($value, $_smarty_tpl->isRenderingCache);
}
?>

        <div menuItemName="<?php echo $_smarty_tpl->tpl_vars['item']->value->getName();?>
" class="cv-card cv-panel-card cv-slide-up<?php if ($_smarty_tpl->tpl_vars['item']->value->getClass()) {?> <?php echo $_smarty_tpl->tpl_vars['item']->value->getClass();
}?>"<?php if ($_smarty_tpl->tpl_vars['item']->value->getAttribute('id')) {?> id="<?php echo $_smarty_tpl->tpl_vars['item']->value->getAttribute('id');?>
"<?php }?>>
            <div class="cv-card-header">
                <?php if ($_smarty_tpl->tpl_vars['item']->value->getExtra('btn-link') && $_smarty_tpl->tpl_vars['item']->value->getExtra('btn-text')) {?>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['item']->value->getExtra('btn-link');?>
" class="cv-button cv-button-sm cv-button-ghost">
                        <?php if ($_smarty_tpl->tpl_vars['item']->value->getExtra('btn-icon')) {?><i class="<?php echo $_smarty_tpl->tpl_vars['item']->value->getExtra('btn-icon');?>
"></i><?php }?>
                        <?php echo $_smarty_tpl->tpl_vars['item']->value->getExtra('btn-text');?>

                    </a>
                <?php }?>
                <h3 class="cv-card-title">
                    <?php if ($_smarty_tpl->tpl_vars['item']->value->hasIcon()) {?><i class="<?php echo $_smarty_tpl->tpl_vars['item']->value->getIcon();?>
"></i>&nbsp;<?php }?>
                    <?php echo $_smarty_tpl->tpl_vars['item']->value->getLabel();?>

                    <?php if ($_smarty_tpl->tpl_vars['item']->value->hasBadge()) {?>&nbsp;<span class="cv-badge"><?php echo $_smarty_tpl->tpl_vars['item']->value->getBadge();?>
</span><?php }?>
                </h3>
            </div>
            <?php if ($_smarty_tpl->tpl_vars['item']->value->hasBodyHtml()) {?>
                <div class="cv-card-body"><?php echo $_smarty_tpl->tpl_vars['item']->value->getBodyHtml();?>
</div>
            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['item']->value->hasChildren()) {?>
                <div class="cv-card-body cv-panel-list">
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['item']->value->getChildren(), 'childItem');
$_smarty_tpl->tpl_vars['childItem']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['childItem']->value) {
$_smarty_tpl->tpl_vars['childItem']->do_else = false;
?>
                        <?php if ($_smarty_tpl->tpl_vars['childItem']->value->getUri()) {?>
                            <a href="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getUri();?>
" class="cv-panel-link<?php if ($_smarty_tpl->tpl_vars['childItem']->value->getClass()) {?> <?php echo $_smarty_tpl->tpl_vars['childItem']->value->getClass();
}?>"<?php if ($_smarty_tpl->tpl_vars['childItem']->value->getAttribute('dataToggleTab')) {?> data-toggle="tab"<?php }
if ($_smarty_tpl->tpl_vars['childItem']->value->getAttribute('target')) {?> target="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getAttribute('target');?>
"<?php }?> id="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getId();?>
">
                                <?php if ($_smarty_tpl->tpl_vars['childItem']->value->hasIcon()) {?><i class="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getIcon();?>
"></i><?php }?>
                                <?php echo $_smarty_tpl->tpl_vars['childItem']->value->getLabel();?>

                                <?php if ($_smarty_tpl->tpl_vars['childItem']->value->hasBadge()) {?><span class="cv-badge"><?php echo $_smarty_tpl->tpl_vars['childItem']->value->getBadge();?>
</span><?php }?>
                            </a>
                        <?php } else { ?>
                            <div class="cv-panel-link<?php if ($_smarty_tpl->tpl_vars['childItem']->value->getClass()) {?> <?php echo $_smarty_tpl->tpl_vars['childItem']->value->getClass();
}?>" id="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getId();?>
">
                                <?php if ($_smarty_tpl->tpl_vars['childItem']->value->hasIcon()) {?><i class="<?php echo $_smarty_tpl->tpl_vars['childItem']->value->getIcon();?>
"></i><?php }?>
                                <?php echo $_smarty_tpl->tpl_vars['childItem']->value->getLabel();?>

                                <?php if ($_smarty_tpl->tpl_vars['childItem']->value->hasBadge()) {?><span class="cv-badge"><?php echo $_smarty_tpl->tpl_vars['childItem']->value->getBadge();?>
</span><?php }?>
                            </div>
                        <?php }?>
                    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </div>
            <?php }?>
            <?php if ($_smarty_tpl->tpl_vars['item']->value->hasFooterHtml()) {?>
                <div class="cv-card-footer"><?php echo $_smarty_tpl->tpl_vars['item']->value->getFooterHtml();?>
</div>
            <?php }?>
        </div>
    <?php
}}
/*/ smarty_template_function_outputHomePanels_21079845846a9cccb325c0e3_31951544 */
}
