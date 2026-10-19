<?php
/* Smarty version 4.5.3, created on 2026-10-19 14:28:28
  from '/home/centralvixcom/public_html/templates/twenty-one/includes/domain-search.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6ad6533c4cc519_92931481',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'cc6133b66f011e3fbfc8eeebd662d46eddbf3fbb' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/twenty-one/includes/domain-search.tpl',
      1 => 1790173048,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6ad6533c4cc519_92931481 (Smarty_Internal_Template $_smarty_tpl) {
?><form method="post" action="domainchecker.php" id="frmDomainHomepage">
    <div class="home-domain-search bg-white">
        <div class="container">
            <div class="p-5 clearfix">
                <h2 class="text-center"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"secureYourDomainShort"),$_smarty_tpl ) );?>
</h2>
                <input type="hidden" name="transfer" />
                <div class="input-group-wrapper">
                    <div class="input-group<?php if ($_smarty_tpl->tpl_vars['showAdvancedSearchOptions']->value) {?> advanced-input<?php }?>">
                        <?php if ($_smarty_tpl->tpl_vars['showAdvancedSearchOptions']->value) {?>
                            <textarea name="message"
                                      id="message"
                                      title="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'domainSearch.domainOrAiPrompt'),$_smarty_tpl ) );?>
"
                                      data-placement="left"
                                      data-trigger="manual"
                                      placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'domainSearch.domainOrAiInstruction'),$_smarty_tpl ) );?>
"></textarea>
                            <select name="tlds[]" class="multiselect multiselect-filter" multiple="multiple" data-placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'domainSearch.tlds'),$_smarty_tpl ) );?>
" data-min-selection="1">
                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['tlds']->value, 'tld');
$_smarty_tpl->tpl_vars['tld']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['tld']->value) {
$_smarty_tpl->tpl_vars['tld']->do_else = false;
?>
                                    <option<?php if (in_array($_smarty_tpl->tpl_vars['tld']->value,$_smarty_tpl->tpl_vars['selectedTlds']->value)) {?> selected <?php if (count($_smarty_tpl->tpl_vars['selectedTlds']->value) <= 1) {?>disabled="disabled"<?php }
}?> value="<?php echo $_smarty_tpl->tpl_vars['tld']->value;?>
"><?php echo $_smarty_tpl->tpl_vars['tld']->value;?>
</option>
                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            </select>
                            <select name="maxLength" class="multiselect" data-placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'domainSearch.maxLength'),$_smarty_tpl ) );?>
">
                                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['searchLengths']->value, 'len');
$_smarty_tpl->tpl_vars['len']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['len']->value) {
$_smarty_tpl->tpl_vars['len']->do_else = false;
?>
                                    <option value="<?php echo $_smarty_tpl->tpl_vars['len']->value;?>
" <?php if ($_smarty_tpl->tpl_vars['maxLength']->value === $_smarty_tpl->tpl_vars['len']->value) {?>selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['len']->value;?>
</option>
                                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                            </select>
                            <label>
                                <input type="checkbox" class="no-icheck" name="filter" <?php if ($_smarty_tpl->tpl_vars['safeSearchSelected']->value) {?>checked<?php }?>><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"domainSearch.safeSearch"),$_smarty_tpl ) );?>

                            </label>
                        <?php } else { ?>
                            <input type="text" class="form-control" name="domain" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"exampledomain"),$_smarty_tpl ) );?>
" autocapitalize="none">
                        <?php }?>
                            <span class="input-group-append d-none d-sm-block">
                                <?php if ($_smarty_tpl->tpl_vars['registerdomainenabled']->value) {?>
                                    <button type="submit" class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
" id="btnDomainSearch">
                                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"search"),$_smarty_tpl ) );
if ($_smarty_tpl->tpl_vars['showAdvancedSearchOptions']->value) {?>  <i class="fa-regular fa-sparkles"></i><?php }?>
                                    </button>
                                <?php }?>
                                <?php if ($_smarty_tpl->tpl_vars['transferdomainenabled']->value) {?>
                                    <button type="submit" id="btnTransfer" data-domain-action="transfer" class="btn btn-success<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
">
                                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"domainstransfer"),$_smarty_tpl ) );?>

                                    </button>
                                <?php }?>
                            </span>
                    </div>
                </div>
                <div class="row d-sm-none">
                    <?php if ($_smarty_tpl->tpl_vars['registerdomainenabled']->value) {?>
                        <div class="col-6">
                            <button type="submit" class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
 btn-block" id="btnDomainSearch2">
                                <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"search"),$_smarty_tpl ) );
if ($_smarty_tpl->tpl_vars['showAdvancedSearchOptions']->value) {?>  <i class="fa-regular fa-sparkles"></i><?php }?>
                            </button>
                        </div>
                    <?php }?>
                    <?php if ($_smarty_tpl->tpl_vars['transferdomainenabled']->value) {?>
                        <div class="col-6">
                            <button type="submit" id="btnTransfer2" data-domain-action="transfer" class="btn btn-success<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
 btn-block">
                                <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"domainstransfer"),$_smarty_tpl ) );?>

                            </button>
                        </div>
                    <?php }?>
                </div>
                <?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/captcha.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

                <?php if ($_smarty_tpl->tpl_vars['featuredTlds']->value) {?>
                    <ul class="tld-logos">
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['featuredTlds']->value, 'tldinfo', false, 'num');
$_smarty_tpl->tpl_vars['tldinfo']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['num']->value => $_smarty_tpl->tpl_vars['tldinfo']->value) {
$_smarty_tpl->tpl_vars['tldinfo']->do_else = false;
?>
                            <?php if ($_smarty_tpl->tpl_vars['num']->value < 3) {?>
                                <li>
                                    <img src="<?php echo $_smarty_tpl->tpl_vars['BASE_PATH_IMG']->value;?>
/tld_logos/<?php echo $_smarty_tpl->tpl_vars['tldinfo']->value['tldNoDots'];?>
.png" alt="<?php echo $_smarty_tpl->tpl_vars['tldinfo']->value['tld'];?>
">
                                    <?php if (is_object($_smarty_tpl->tpl_vars['tldinfo']->value['register'])) {?>
                                        <?php echo $_smarty_tpl->tpl_vars['tldinfo']->value['register']->toFull();?>

                                    <?php } else { ?>
                                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>"domainregnotavailable"),$_smarty_tpl ) );?>

                                    <?php }?>
                                </li>
                            <?php }?>
                        <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                    </ul>
                <?php }?>

                <a href="<?php echo routePath('domain-pricing');?>
" class="btn btn-link btn-sm float-right"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'viewAllPricing'),$_smarty_tpl ) );?>
</a>
            </div>
        </div>
    </div>
</form>

<?php if ($_smarty_tpl->tpl_vars['showAdvancedSearchOptions']->value) {
echo '<script'; ?>
>
    $(document).ready(function() {
        jQuery('#frmDomainHomepage .multiselect').each(function () {
            const enableFiltering = $(this).hasClass('multiselect-filter');
            const minSelection = jQuery(this).data('min-selection');
            $(this).multiselect({
                onChange: function (element) {
                    const closestSelect = element.closest('select');
                    const selectedOptions = closestSelect.find('option:selected');
                    if (minSelection === undefined) {
                        return;
                    }
                    const atMinOptions = selectedOptions.length <= minSelection;
                    const targetOptions = atMinOptions ? selectedOptions : closestSelect.find('option');
                    targetOptions.each(function () {
                        const inputElement = jQuery('input[value="' + jQuery(this).val() + '"]');
                        inputElement.prop('disabled', atMinOptions ? 'disabled' : false);
                    });
                },
                buttonText: function(options, select) {
                    return select.data('placeholder');
                },
                maxHeight: 200,
                includeFilterClearBtn: false,
                enableCaseInsensitiveFiltering: enableFiltering,
            });
        })
    });
<?php echo '</script'; ?>
>
<?php }?>

<?php }
}
