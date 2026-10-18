<?php
/* Smarty version 4.5.3, created on 2026-08-29 23:14:08
  from '/home/centralvixcom/public_html/templates/centralvix-client/login.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9367c0610120_57862530',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '08a48f2d5defb5f34b6fee290005e714740e7117' => 
    array (
      0 => '/home/centralvixcom/public_html/templates/centralvix-client/login.tpl',
      1 => 1786327394,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a9367c0610120_57862530 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="cv-login-page">
    <div class="cv-login-visual cv-fade-in">
        <div class="cv-login-visual-grid" aria-hidden="true"></div>
        <div class="cv-login-visual-glow" aria-hidden="true"></div>
        <div class="cv-login-visual-content">
            <img src="<?php echo $_smarty_tpl->tpl_vars['WEB_ROOT']->value;?>
/templates/<?php echo $_smarty_tpl->tpl_vars['template']->value;?>
/assets/img/logo-light.svg" alt="CentralVix" class="cv-logo cv-logo-login">
            <h1 class="cv-login-tagline">Sua tecnologia. Seu controle. Em um só lugar.</h1>
            <p class="cv-login-desc">Gerencie hospedagem, cloud, streaming, pagamentos e suporte através da CentralVix.</p>
        </div>
    </div>

    <div class="cv-login-form-wrap cv-slide-up">
        <div class="cv-card cv-login-card">
            <?php if ($_smarty_tpl->tpl_vars['remote_auth_prelinked']->value) {?>
                <div class="cv-alert cv-alert-info"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'remoteAuthn.oneTimeAuthRequired'),$_smarty_tpl ) );?>
</div>
            <?php }?>

            <div class="cv-login-card-head">
                <h2 class="cv-login-title">Bem-vindo de volta</h2>
                <p class="cv-login-subtitle"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'userLogin.signInToContinue'),$_smarty_tpl ) );?>
</p>
            </div>

            <?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/flashmessage.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?>

            <form method="post" action="<?php echo routePath('login-validate');?>
" class="cv-form" role="form">
                <div class="cv-form-group">
                    <label for="inputEmail"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'clientareaemail'),$_smarty_tpl ) );?>
</label>
                    <input type="email" name="username" class="cv-input form-control" id="inputEmail" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'enteremail'),$_smarty_tpl ) );?>
" autofocus>
                </div>
                <div class="cv-form-group">
                    <div class="cv-form-label-row">
                        <label for="inputPassword"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'clientareapassword'),$_smarty_tpl ) );?>
</label>
                        <a href="<?php echo routePath('password-reset-begin');?>
" class="cv-link"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'forgotpw'),$_smarty_tpl ) );?>
</a>
                    </div>
                    <input type="password" name="password" class="cv-input form-control" id="inputPassword" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'clientareapassword'),$_smarty_tpl ) );?>
" autocomplete="off">
                </div>

                <?php if ($_smarty_tpl->tpl_vars['captcha']->value->isEnabled()) {?>
                    <div class="cv-form-group"><?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/captcha.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, true);
?></div>
                <?php }?>

                <div class="cv-form-group cv-form-check">
                    <label class="cv-checkbox">
                        <input type="checkbox" name="rememberme" value="1">
                        <span><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'loginrememberme'),$_smarty_tpl ) );?>
</span>
                    </label>
                </div>

                <button type="submit" class="cv-button cv-button-primary cv-button-block cv-button-lg">
                    <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'loginbutton'),$_smarty_tpl ) );?>

                </button>
            </form>

            <div class="cv-login-footer-links">
                <span><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'userLogin.notRegistered'),$_smarty_tpl ) );?>
</span>
                <a href="<?php echo routePath('register');?>
" class="cv-link cv-link-strong"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'userLogin.createAccount'),$_smarty_tpl ) );?>
</a>
            </div>

            <?php $_smarty_tpl->_subTemplateRender(((string)$_smarty_tpl->tpl_vars['template']->value)."/includes/linkedaccounts.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array('linkContext'=>"login",'customFeedback'=>true), 0, true);
?>
        </div>
    </div>
</div>
<?php }
}
