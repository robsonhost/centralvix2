<?php
/* Smarty version 4.5.3, created on 2026-08-29 23:14:06
  from '/home/centralvixcom/public_html/templates/centralvix-client/password-reset-email-prompt.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9367bebf2e93_64599476',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5c3338b97b13f97bfd91af3f40f44ca67b3eeff6' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/centralvix-client/password-reset-email-prompt.tpl',
      1 => 1786328740,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a9367bebf2e93_64599476 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="mb-4">
    <h6 class="h3"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'pwreset'),$_smarty_tpl ) );?>
</h6>
    <p class="text-muted mb-0"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'pwresetemailneeded'),$_smarty_tpl ) );?>
</p>
</div>

<form method="post" action="<?php echo routePath('password-reset-validate-email');?>
" role="form">
    <input type="hidden" name="action" value="reset" />

    <div class="form-group">
        <label for="inputEmail"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'loginemail'),$_smarty_tpl ) );?>
</label>
        <div class="input-group input-group-merge">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
            </div>
            <input type="email" class="form-control" name="email" id="inputEmail" placeholder="name@example.com" autofocus>
          </div>
    </div>

    <?php if ($_smarty_tpl->tpl_vars['captcha']->value && $_smarty_tpl->tpl_vars['captcha']->value->isEnabled() && $_smarty_tpl->tpl_vars['showCaptchaAfterLimit']->value) {?>
        <div class="text-center margin-bottom">
            <?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/captcha.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>
        </div>
    <?php }?>

    <div class="form-group text-center">
        <button type="submit" id="resetPasswordButton" <?php if ($_smarty_tpl->tpl_vars['showCaptchaAfterLimit']->value) {?>data-captcha-required="true"<?php }?> class="btn btn-primary<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
">
            <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'pwresetsubmit'),$_smarty_tpl ) );?>

        </button>
    </div>

</form>

<?php }
}
