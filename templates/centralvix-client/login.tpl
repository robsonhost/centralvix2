<div class="cv-login-page">
    <div class="cv-login-visual cv-fade-in">
        <div class="cv-login-visual-grid" aria-hidden="true"></div>
        <div class="cv-login-visual-glow" aria-hidden="true"></div>
        <div class="cv-login-visual-content">
            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-light.svg" alt="CentralVix" class="cv-logo cv-logo-login">
            <h1 class="cv-login-tagline">Sua tecnologia. Seu controle. Em um só lugar.</h1>
            <p class="cv-login-desc">Gerencie hospedagem, cloud, streaming, pagamentos e suporte através da CentralVix.</p>
        </div>
    </div>

    <div class="cv-login-form-wrap cv-slide-up">
        <div class="cv-card cv-login-card">
            {if $remote_auth_prelinked}
                <div class="cv-alert cv-alert-info">{lang key='remoteAuthn.oneTimeAuthRequired'}</div>
            {/if}

            <div class="cv-login-card-head">
                <h2 class="cv-login-title">Bem-vindo de volta</h2>
                <p class="cv-login-subtitle">{lang key='userLogin.signInToContinue'}</p>
            </div>

            {include file="$template/includes/flashmessage.tpl"}

            <form method="post" action="{routePath('login-validate')}" class="cv-form" role="form">
                <div class="cv-form-group">
                    <label for="inputEmail">{lang key='clientareaemail'}</label>
                    <input type="email" name="username" class="cv-input form-control" id="inputEmail" placeholder="{lang key='enteremail'}" autofocus>
                </div>
                <div class="cv-form-group">
                    <div class="cv-form-label-row">
                        <label for="inputPassword">{lang key='clientareapassword'}</label>
                        <a href="{routePath('password-reset-begin')}" class="cv-link">{lang key='forgotpw'}</a>
                    </div>
                    <input type="password" name="password" class="cv-input form-control" id="inputPassword" placeholder="{lang key='clientareapassword'}" autocomplete="off">
                </div>

                {if $captcha->isEnabled()}
                    <div class="cv-form-group">{include file="$template/includes/captcha.tpl"}</div>
                {/if}

                <div class="cv-form-group cv-form-check">
                    <label class="cv-checkbox">
                        <input type="checkbox" name="rememberme" value="1">
                        <span>{lang key='loginrememberme'}</span>
                    </label>
                </div>

                <button type="submit" class="cv-button cv-button-primary cv-button-block cv-button-lg">
                    {lang key='loginbutton'}
                </button>
            </form>

            <div class="cv-login-footer-links">
                <span>{lang key='userLogin.notRegistered'}</span>
                <a href="{routePath('register')}" class="cv-link cv-link-strong">{lang key='userLogin.createAccount'}</a>
            </div>

            {include file="$template/includes/linkedaccounts.tpl" linkContext="login" customFeedback=true}
        </div>
    </div>
</div>
