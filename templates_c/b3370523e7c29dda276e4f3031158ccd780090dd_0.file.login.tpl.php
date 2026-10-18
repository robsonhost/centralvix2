<?php
/* Smarty version 4.5.3, created on 2026-08-30 01:21:55
  from '/home/centralvixcom/public_html/admin/templates/login.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6a9385b324e5d6_88138053',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'b3370523e7c29dda276e4f3031158ccd780090dd' => 
    array (
      0 => '/home/centralvixcom/public_html/admin/templates/login.tpl',
      1 => 1788052781,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6a9385b324e5d6_88138053 (Smarty_Internal_Template $_smarty_tpl) {
?><!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CENTRALVIX — <?php echo $_smarty_tpl->tpl_vars['displayTitle']->value;?>
</title>

        <?php echo WHMCS\View\Asset::fontCssInclude('open-sans-family.css');?>

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link href="templates/login.min.css" rel="stylesheet">
        <style>
            html, body {
                min-height: 100%;
            }
            body {
                margin: 0;
                font-family: Inter, "Segoe UI", system-ui, sans-serif;
                background:
                    radial-gradient(ellipse 80% 50% at 80% 0%, rgba(20, 108, 255, 0.22), transparent 55%),
                    repeating-linear-gradient(90deg, rgba(255,255,255,.035) 0 1px, transparent 1px 72px),
                    repeating-linear-gradient(0deg, rgba(255,255,255,.03) 0 1px, transparent 1px 72px),
                    #020b1c;
                color: #fff;
            }
            .login-container {
                width: min(420px, calc(100% - 2rem));
                margin: 8vh auto 2rem;
                padding: 0;
                background: transparent;
                box-shadow: none;
            }
            .logo {
                text-align: center;
                margin: 0 0 1.25rem;
            }
            .logo a {
                text-decoration: none;
            }
            .cv-admin-wordmark {
                display: block;
                font-size: 1.7rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                color: #fff;
            }
            .cv-admin-wordmark span { color: #2583ff; }
            .cv-admin-tag {
                display: block;
                margin-top: 4px;
                font-size: 0.68rem;
                font-weight: 600;
                letter-spacing: 0.16em;
                color: rgba(255,255,255,.7);
            }
            .login-body {
                padding: 1.6rem 1.45rem 1.35rem;
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 16px;
                background: rgba(8, 18, 38, .78);
                box-shadow: 0 18px 50px rgba(0,0,0,.28);
                backdrop-filter: blur(16px);
            }
            .login-body h2 {
                margin: 0 0 1rem;
                color: #fff;
                font-size: 1.35rem;
                font-weight: 800;
            }
            .form-control {
                height: 48px;
                border: 1px solid rgba(255,255,255,.12);
                border-radius: 10px;
                background: rgba(2, 11, 28, .65);
                color: #fff;
                box-shadow: none;
            }
            .form-control:focus {
                border-color: #146cff;
                box-shadow: 0 0 0 3px rgba(20,108,255,.18);
            }
            .btn-primary {
                height: 46px;
                border: 0;
                border-radius: 10px;
                background: linear-gradient(180deg, #2583ff 0%, #146cff 100%);
                font-weight: 700;
            }
            .btn-primary:hover,
            .btn-primary:focus {
                background: #2583ff;
                filter: brightness(1.05);
            }
            .checkbox label,
            .login-body p,
            .footer,
            .footer a {
                color: #94a3b8;
            }
            .footer {
                margin-top: 1rem;
                text-align: center;
            }
            .footer a { color: #2583ff; font-weight: 600; }
            .alert {
                border-radius: 10px;
            }
            .language-chooser {
                width: min(420px, calc(100% - 2rem));
                margin: 0 auto 1rem;
            }
            .poweredby,
            .poweredby a {
                color: #64748b;
                font-size: 12px;
            }
            .logo img { display: none; }
        </style>
        <!--[if lt IE 9]>
          <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"><?php echo '</script'; ?>
>
          <?php echo '<script'; ?>
 src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"><?php echo '</script'; ?>
>
        <![endif]-->
      </head>
      <body>
        <?php if ($_smarty_tpl->tpl_vars['captcha']->value) {
echo $_smarty_tpl->tpl_vars['captcha']->value->getMarkup();
}?>
        <div class="login-container">
            <h1 class="logo">
                <a href="login.php">
                    <span class="cv-admin-wordmark">CENTRAL<span>VIX</span></span>
                    <span class="cv-admin-tag">PAINEL ADMINISTRATIVO</span>
                    <img src="<?php echo $_smarty_tpl->tpl_vars['BASE_PATH_IMG']->value;?>
/whmcs.png" alt="CENTRALVIX" />
                </a>
            </h1>
            <div class="login-body">
                <h2><?php echo $_smarty_tpl->tpl_vars['displayTitle']->value;?>
</h2>
                <?php if ($_smarty_tpl->tpl_vars['infoMsg']->value) {?>
                    <div id="alertLoginInfo" class="alert alert-info text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['infoMsg']->value;?>

                    </div>
                <?php }?>
                <?php if ($_smarty_tpl->tpl_vars['successMsg']->value) {?>
                    <div id="alertLoginSuccess" class="alert alert-success text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['successMsg']->value;?>

                    </div>
                <?php }?>
                <?php if ($_smarty_tpl->tpl_vars['errorMsg']->value) {?>
                    <div id="alertLoginError" class="alert alert-danger text-center" role="alert">
                        <?php echo $_smarty_tpl->tpl_vars['errorMsg']->value;?>

                    </div>
                <?php }?>
                <?php if ($_smarty_tpl->tpl_vars['step']->value == "login") {?>
                    <form method="post" action="dologin.php">
                        <input type="hidden" name="redirect" value="<?php echo $_smarty_tpl->tpl_vars['redirectUri']->value;?>
" />
                        <div class="form-group">
                            <input type="text" name="username" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'fields.username'),$_smarty_tpl ) );?>
" value="<?php echo $_smarty_tpl->tpl_vars['username']->value;?>
"<?php if (!$_smarty_tpl->tpl_vars['username']->value) {?> autofocus<?php }?> />
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'fields.password'),$_smarty_tpl ) );?>
"<?php if ($_smarty_tpl->tpl_vars['username']->value) {?> autofocus<?php }?> />
                        </div>
                        <?php if ($_smarty_tpl->tpl_vars['captcha']->value->isEnabled() && $_smarty_tpl->tpl_vars['captcha']->value->isEnabledForForm($_smarty_tpl->tpl_vars['captchaForm']->value)) {?>
                            <?php if ($_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled() && !$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isInvisible()) {?>
                                <div class="form-group recaptcha-container"></div>
                            <?php } elseif (!$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled()) {?>
                                <div class="form-group captcha-container text-center">
                                    <p>
                                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.captchaverify'),$_smarty_tpl ) );?>

                                    </p>
                                    <div class="row">
                                        <div class="col-xs-6">
                                            <img id="inputCaptchaImage" class="captchaimage" src="../includes/verifyimage.php" align="middle" />
                                        </div>

                                        <div class="col-xs-6">
                                            <input id="inputCaptcha" type="text" name="code" maxlength="6" class="form-control" autocomplete="off" />
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        <?php }?>
                        <div class="row">
                            <div class="col-sm-7">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="rememberme" value="1">
                                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.rememberme'),$_smarty_tpl ) );?>

                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <input type="submit" value="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.login'),$_smarty_tpl ) );?>
" class="btn btn-primary btn-block<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
" />
                            </div>
                        </div>
                    </form>
                <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == "reset") {?>
                    <form action="login.php" method="post" id="<?php if (!empty($_smarty_tpl->tpl_vars['verify']->value)) {?>frmPasswordChange<?php } else { ?>frmResetPassword<?php }?>">
                        <input type="hidden" name="action" value="reset" />
                        <?php if (!empty($_smarty_tpl->tpl_vars['verify']->value)) {?>
                            <input type="hidden" name="sub" value="newPassword" />
                            <input type="hidden" name="verify" value="<?php echo $_smarty_tpl->tpl_vars['verify']->value;?>
" />
                            <div class="form-group">
                                <input type="password" id="password" name="password" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.newpassword'),$_smarty_tpl ) );?>
" autofocus autocomplete="off" data-placement="left" data-trigger="manual" />
                                <span class="form-control-feedback glyphicon glyphicon-password"></span>
                            </div>
                            <div class="form-group">
                                <input type="password" id="passwordConfirm" name="password2" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.newpasswordverify'),$_smarty_tpl ) );?>
" autocomplete="off" data-placement="left" data-trigger="manual" />
                                <span class="form-control-feedback glyphicon glyphicon-password"></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" id="setPasswordButton" value="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.resetpassword'),$_smarty_tpl ) );?>
" class="btn btn-primary btn-block<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
" />
                            </div>
                        <?php } else { ?>
                            <input type="hidden" name="sub" value="send" />
                            <div class="form-group">
                                <input type="text" name="email" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.usernameoremail'),$_smarty_tpl ) );?>
" autofocus />
                            </div>
                            <?php if ($_smarty_tpl->tpl_vars['captcha']->value->isEnabled() && $_smarty_tpl->tpl_vars['captcha']->value->isEnabledForForm($_smarty_tpl->tpl_vars['captchaForm']->value)) {?>
                                <?php if ($_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled() && !$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isInvisible()) {?>
                                    <div class="form-group recaptcha-container"></div>
                                <?php } elseif (!$_smarty_tpl->tpl_vars['captcha']->value->recaptcha->isEnabled()) {?>
                                    <div class="form-group captcha-container text-center">
                                        <p>
                                            <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.captchaverify'),$_smarty_tpl ) );?>

                                        </p>
                                        <div class="row">
                                            <div class="col-xs-6">
                                                <img id="inputCaptchaImage" class="captchaimage" src="../includes/verifyimage.php" align="middle" />
                                            </div>

                                            <div class="col-xs-6">
                                                <input id="inputCaptcha" type="text" name="code" maxlength="6" class="form-control" autocomplete="off" />
                                            </div>
                                        </div>
                                    </div>
                                <?php }?>
                            <?php }?>
                            <div class="form-group">
                                <input type="submit" value="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.resetpassword'),$_smarty_tpl ) );?>
" class="btn btn-primary btn-block<?php echo $_smarty_tpl->tpl_vars['captcha']->value->getButtonClass($_smarty_tpl->tpl_vars['captchaForm']->value);?>
" />
                            </div>
                        <?php }?>
                    </form>
                <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == "twofa") {?>
                    <div class="text-center" align="center">
                        <form method="post" action="<?php echo $_smarty_tpl->tpl_vars['issuerurl']->value;?>
dologin.php" role="form">
                            <?php echo $_smarty_tpl->tpl_vars['challengeHtml']->value;?>

                        </form>
                    </div>
                <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == "twofabackupcode") {?>
                    <?php if ($_smarty_tpl->tpl_vars['successMsg']->value) {?>
                        <p><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'twofa.backupcodeexpl'),$_smarty_tpl ) );?>
</p>
                        <form method="post" action="dologin.php">
                            <input type="hidden" name="redirect" value="<?php echo $_smarty_tpl->tpl_vars['redirectUri']->value;?>
" />
                            <div class="form-group">
                                <input type="submit" value="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'global.continue'),$_smarty_tpl ) );?>
 &raquo;" class="btn btn-primary btn-block" />
                            </div>
                        </form>
                    <?php } else { ?>
                        <form action="dologin.php" method="post">
                            <input type="hidden" name="backupcode" value="1" />
                            <input type="hidden" name="redirect" value="<?php echo $_smarty_tpl->tpl_vars['redirectUri']->value;?>
" />
                            <div class="form-group">
                                <input type="text" name="code" class="form-control" placeholder="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.backupcode'),$_smarty_tpl ) );?>
" autofocus />
                            </div>
                            <div class="form-group">
                                <input type="submit" value="<?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.login'),$_smarty_tpl ) );?>
" class="btn btn-primary btn-block" />
                            </div>
                        </form>
                    <?php }?>
                <?php }?>
            </div>
            <div class="footer">
                <?php if ($_smarty_tpl->tpl_vars['step']->value == "login") {?>
                    <?php if ($_smarty_tpl->tpl_vars['showPasswordResetLink']->value) {?>
                        <a href="login.php?action=reset">
                            <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.forgotpassword'),$_smarty_tpl ) );?>

                        </a>
                    <?php } else { ?>
                        <span>&nbsp;</span>
                    <?php }?>
                <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == "reset") {?>
                    <a href="login.php">
                        &laquo; <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.backtologin'),$_smarty_tpl ) );?>

                    </a>
                <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == "twofa") {?>
                    <a href="login.php?<?php if ($_smarty_tpl->tpl_vars['redirectUri']->value) {?>redirect=<?php echo call_user_func_array($_smarty_tpl->registered_plugins[ 'modifier' ][ 'urlencode' ][ 0 ], array( $_smarty_tpl->tpl_vars['redirectUri']->value ));?>
&amp;<?php }?>backupcode=1">
                        <?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.twofacantaccess2ndfactor'),$_smarty_tpl ) );?>
<br /><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.twofaloginusingbackupcode'),$_smarty_tpl ) );?>

                    </a>
                <?php }?>
            </div>
        </div>
        <div class="language-chooser">
            <div class="btn-group pull-right">
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span id="languageName"><?php echo call_user_func_array( $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['lang'][0], array( array('key'=>'login.chooselanguage'),$_smarty_tpl ) );?>
</span> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['locales']->value, 'locale');
$_smarty_tpl->tpl_vars['locale']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['locale']->value) {
$_smarty_tpl->tpl_vars['locale']->do_else = false;
?>
                        <li><a href="?language=<?php echo $_smarty_tpl->tpl_vars['locale']->value['language'];?>
"><?php echo $_smarty_tpl->tpl_vars['locale']->value['localisedName'];?>
</a></li>
                    <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                </ul>
            </div>
        </div>
        <div class="poweredby text-center">
            <a href="http://www.whmcs.com/" target="_blank">Powered by WHMCS</a>
        </div>
        <?php if ($_smarty_tpl->tpl_vars['captcha']->value) {?>
        <?php echo '<script'; ?>
 type="text/javascript">
            <?php echo $_smarty_tpl->tpl_vars['captcha']->value->recaptcha->getPageJs();?>

        <?php echo '</script'; ?>
>
        <?php }?>
        <?php echo '<script'; ?>
 type="text/javascript" src="templates/login.min.js"><?php echo '</script'; ?>
>
    </body>
</html>
<?php }
}
