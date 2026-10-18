<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>CENTRALVIX — {$displayTitle}</title>

        {\WHMCS\View\Asset::fontCssInclude('open-sans-family.css')}
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
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
      </head>
      <body>
        {if $captcha}{$captcha->getMarkup()}{/if}
        <div class="login-container">
            <h1 class="logo">
                <a href="login.php">
                    <span class="cv-admin-wordmark">CENTRAL<span>VIX</span></span>
                    <span class="cv-admin-tag">PAINEL ADMINISTRATIVO</span>
                    <img src="{$BASE_PATH_IMG}/whmcs.png" alt="CENTRALVIX" />
                </a>
            </h1>
            <div class="login-body">
                <h2>{$displayTitle}</h2>
                {if $infoMsg}
                    <div id="alertLoginInfo" class="alert alert-info text-center" role="alert">
                        {$infoMsg}
                    </div>
                {/if}
                {if $successMsg}
                    <div id="alertLoginSuccess" class="alert alert-success text-center" role="alert">
                        {$successMsg}
                    </div>
                {/if}
                {if $errorMsg}
                    <div id="alertLoginError" class="alert alert-danger text-center" role="alert">
                        {$errorMsg}
                    </div>
                {/if}
                {if $step eq "login"}
                    <form method="post" action="dologin.php">
                        <input type="hidden" name="redirect" value="{$redirectUri}" />
                        <div class="form-group">
                            <input type="text" name="username" class="form-control" placeholder="{lang key='fields.username'}" value="{$username}"{if !$username} autofocus{/if} />
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" class="form-control" placeholder="{lang key='fields.password'}"{if $username} autofocus{/if} />
                        </div>
                        {if $captcha->isEnabled() && $captcha->isEnabledForForm($captchaForm)}
                            {if $captcha->recaptcha->isEnabled() && !$captcha->recaptcha->isInvisible()}
                                <div class="form-group recaptcha-container"></div>
                            {elseif !$captcha->recaptcha->isEnabled()}
                                <div class="form-group captcha-container text-center">
                                    <p>
                                        {lang key='login.captchaverify'}
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
                            {/if}
                        {/if}
                        <div class="row">
                            <div class="col-sm-7">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="rememberme" value="1">
                                        {lang key='login.rememberme'}
                                    </label>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <input type="submit" value="{lang key='login.login'}" class="btn btn-primary btn-block{$captcha->getButtonClass($captchaForm)}" />
                            </div>
                        </div>
                    </form>
                {elseif $step eq "reset"}
                    <form action="login.php" method="post" id="{if !empty($verify)}frmPasswordChange{else}frmResetPassword{/if}">
                        <input type="hidden" name="action" value="reset" />
                        {if !empty($verify)}
                            <input type="hidden" name="sub" value="newPassword" />
                            <input type="hidden" name="verify" value="{$verify}" />
                            <div class="form-group">
                                <input type="password" id="password" name="password" class="form-control" placeholder="{lang key='login.newpassword'}" autofocus autocomplete="off" data-placement="left" data-trigger="manual" />
                                <span class="form-control-feedback glyphicon glyphicon-password"></span>
                            </div>
                            <div class="form-group">
                                <input type="password" id="passwordConfirm" name="password2" class="form-control" placeholder="{lang key='login.newpasswordverify'}" autocomplete="off" data-placement="left" data-trigger="manual" />
                                <span class="form-control-feedback glyphicon glyphicon-password"></span>
                            </div>
                            <div class="form-group">
                                <input type="submit" id="setPasswordButton" value="{lang key='login.resetpassword'}" class="btn btn-primary btn-block{$captcha->getButtonClass($captchaForm)}" />
                            </div>
                        {else}
                            <input type="hidden" name="sub" value="send" />
                            <div class="form-group">
                                <input type="text" name="email" class="form-control" placeholder="{lang key='login.usernameoremail'}" autofocus />
                            </div>
                            {if $captcha->isEnabled() && $captcha->isEnabledForForm($captchaForm)}
                                {if $captcha->recaptcha->isEnabled() && !$captcha->recaptcha->isInvisible()}
                                    <div class="form-group recaptcha-container"></div>
                                {elseif !$captcha->recaptcha->isEnabled()}
                                    <div class="form-group captcha-container text-center">
                                        <p>
                                            {lang key='login.captchaverify'}
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
                                {/if}
                            {/if}
                            <div class="form-group">
                                <input type="submit" value="{lang key='login.resetpassword'}" class="btn btn-primary btn-block{$captcha->getButtonClass($captchaForm)}" />
                            </div>
                        {/if}
                    </form>
                {elseif $step eq "twofa"}
                    <div class="text-center" align="center">
                        <form method="post" action="{$issuerurl}dologin.php" role="form">
                            {$challengeHtml}
                        </form>
                    </div>
                {elseif $step eq "twofabackupcode"}
                    {if $successMsg}
                        <p>{lang key='twofa.backupcodeexpl'}</p>
                        <form method="post" action="dologin.php">
                            <input type="hidden" name="redirect" value="{$redirectUri}" />
                            <div class="form-group">
                                <input type="submit" value="{lang key='global.continue'} &raquo;" class="btn btn-primary btn-block" />
                            </div>
                        </form>
                    {else}
                        <form action="dologin.php" method="post">
                            <input type="hidden" name="backupcode" value="1" />
                            <input type="hidden" name="redirect" value="{$redirectUri}" />
                            <div class="form-group">
                                <input type="text" name="code" class="form-control" placeholder="{lang key='login.backupcode'}" autofocus />
                            </div>
                            <div class="form-group">
                                <input type="submit" value="{lang key='login.login'}" class="btn btn-primary btn-block" />
                            </div>
                        </form>
                    {/if}
                {/if}
            </div>
            <div class="footer">
                {if $step eq "login"}
                    {if $showPasswordResetLink}
                        <a href="login.php?action=reset">
                            {lang key='login.forgotpassword'}
                        </a>
                    {else}
                        <span>&nbsp;</span>
                    {/if}
                {elseif $step eq "reset"}
                    <a href="login.php">
                        &laquo; {lang key='login.backtologin'}
                    </a>
                {elseif $step eq "twofa"}
                    <a href="login.php?{if $redirectUri}redirect={$redirectUri|urlencode}&amp;{/if}backupcode=1">
                        {lang key='login.twofacantaccess2ndfactor'}<br />{lang key='login.twofaloginusingbackupcode'}
                    </a>
                {/if}
            </div>
        </div>
        <div class="language-chooser">
            <div class="btn-group pull-right">
                <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                    <span id="languageName">{lang key='login.chooselanguage'}</span> <span class="caret"></span>
                </button>
                <ul class="dropdown-menu" role="menu">
                    {foreach $locales as $locale}
                        <li><a href="?language={$locale.language}">{$locale.localisedName}</a></li>
                    {/foreach}
                </ul>
            </div>
        </div>
        <div class="poweredby text-center">
            <a href="http://www.whmcs.com/" target="_blank">Powered by WHMCS</a>
        </div>
        {if $captcha}
        <script type="text/javascript">
            {$captcha->recaptcha->getPageJs()}
        </script>
        {/if}
        <script type="text/javascript" src="templates/login.min.js"></script>
    </body>
</html>
