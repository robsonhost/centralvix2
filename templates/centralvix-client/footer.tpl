{if $loggedin && !$inShoppingCart && $templatefile != 'login' && $templatefile != 'clientregister' && $templatefile != 'password-reset-container'}
                        </div>{* .cv-page-content *}
                    </div>{* .cv-page-inner *}
                </main>
                {include file="$template/includes/centralvix-footer.tpl"}
            </div>
            {include file="$template/includes/centralvix-bottom-nav.tpl"}
        </div>

{elseif $inShoppingCart}
                </div>
            </div>
        </section>
        {include file="$template/includes/centralvix-checkout-footer.tpl"}

{elseif $templatefile == 'login' || $templatefile == 'clientregister' || $templatefile == 'password-reset-container'}
        </div>{* .cv-auth-shell *}

{else}
                </div>
            </div>
        </section>
        {include file="$template/includes/centralvix-public-footer.tpl"}

{/if}

<div id="cv-toast-root" class="cv-toast-root" aria-live="polite" aria-atomic="true"></div>

<div id="fullpage-overlay" class="w-hidden">
    <div class="outer-wrapper">
        <div class="inner-wrapper">
            <img src="{$WEB_ROOT}/assets/img/overlay-spinner.svg" alt="">
            <br>
            <span class="msg"></span>
        </div>
    </div>
</div>

<div class="modal system-modal fade cv-modal" id="modalAjax" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">{lang key='close'}</span>
                </button>
            </div>
            <div class="modal-body">{lang key='loading'}</div>
            <div class="modal-footer">
                <div class="float-left loader">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    {lang key='loading'}
                </div>
                <button type="button" class="btn btn-default" data-dismiss="modal">{lang key='close'}</button>
                <button type="button" class="btn btn-primary modal-submit">{lang key='submit'}</button>
            </div>
        </div>
    </div>
</div>

<form method="get" action="{$currentpagelinkback}">
    <div class="modal modal-localisation cv-modal" id="modalChooseLanguage" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close text-light" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    {if $languagechangeenabled && count($locales) > 1}
                        <h5 class="h5 pt-5 pb-3">{lang key='chooselanguage'}</h5>
                        <div class="row item-selector">
                            <input type="hidden" name="language" data-current="{$language}" value="{$language}" />
                            {foreach $locales as $locale}
                                <div class="col-4">
                                    <a href="#" class="item{if $language == $locale.language} active{/if}" data-value="{$locale.language}">
                                        {$locale.localisedName}
                                    </a>
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                    {if !$loggedin && $currencies}
                        <p class="h5 pt-5 pb-3">{lang key='choosecurrency'}</p>
                        <div class="row item-selector">
                            <input type="hidden" name="currency" data-current="{$activeCurrency.id}" value="">
                            {foreach $currencies as $selectCurrency}
                                <div class="col-4">
                                    <a href="#" class="item{if $activeCurrency.id == $selectCurrency.id} active{/if}" data-value="{$selectCurrency.id}">
                                        {$selectCurrency.prefix} {$selectCurrency.code}
                                    </a>
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-default">{lang key='apply'}</button>
                </div>
            </div>
        </div>
    </div>
</form>

{if !$loggedin && $adminLoggedIn}
    <a href="{$WEB_ROOT}/logout.php?returntoadmin=1" class="btn btn-return-to-admin cv-return-admin" data-toggle="tooltip" data-placement="bottom" title="{if $adminMasqueradingAsClient}{lang key='adminmasqueradingasclient'} {lang key='logoutandreturntoadminarea'}{else}{lang key='adminloggedin'} {lang key='returntoadminarea'}{/if}">
        <i class="fas fa-redo-alt"></i>
        <span class="d-none d-md-inline-block">{lang key="admin.returnToAdmin"}</span>
    </a>
{/if}

{include file="$template/includes/generate-password.tpl"}
<!-- CVIX-FORCE-ASSETS-v1.3.1 (inline footer — sem include externo) -->
<link rel="stylesheet" href="/templates/centralvix-client/css/all.min.css?v=1.3.1">
<link rel="stylesheet" href="/templates/centralvix-client/css/theme.min.css?v=1.3.1">
<link rel="stylesheet" href="/templates/centralvix-client/assets/css/centralvix-client.bundle.css?v=1.3.1">
<link rel="stylesheet" href="/assets/fonts/css/fontawesome.min.css">
<link rel="stylesheet" href="/assets/fonts/css/fontawesome-solid.min.css">
<script src="/templates/centralvix-client/js/scripts.min.js?v=1.3.1"></script>
<style id="cv-force-inline">
.w-hidden,.modal:not(.show),#fullpage-overlay.w-hidden,#accountNotificationsContent,#modalChooseLanguage,#modalAjax,.popover{display:none!important}
.cv-client-body{font-family:'Open Sans',Segoe UI,sans-serif;background:#F5F8FC;color:#0B1F3A;margin:0}
.cv-client-app{display:flex;min-height:100vh}
.cv-sidebar{position:fixed;top:0;left:0;bottom:0;width:270px;background:#071426;color:#fff;z-index:1050;overflow-y:auto}
.cv-main{flex:1;margin-left:270px;display:flex;flex-direction:column;min-width:0}
.cv-topbar{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;background:#fff;border-bottom:1px solid #D8E3F0;position:sticky;top:0;z-index:1020}
.cv-page{flex:1;padding:1.25rem 1.5rem 5rem}
.cv-nav-list{list-style:none;padding:0;margin:0}
.cv-nav-link{display:flex;align-items:center;gap:.75rem;padding:.7rem .85rem;color:rgba(255,255,255,.85)!important;text-decoration:none!important}
.cv-nav-item.active .cv-nav-link{background:rgba(15,76,255,.35);color:#fff!important}
.cv-hero{background:linear-gradient(135deg,#071426,#0F4CFF);color:#fff;padding:2rem;border-radius:14px;margin-bottom:1.5rem}
.cv-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
.cv-stat-card,.cv-card{background:#fff;border:1px solid #D8E3F0;border-radius:14px;padding:1rem;box-shadow:0 8px 30px rgba(7,20,38,.06)}
.cv-button-primary,.btn-primary{background:linear-gradient(135deg,#0F4CFF,#00C8FF)!important;border:0!important;color:#fff!important;border-radius:10px!important;padding:.6rem 1rem;font-weight:600;text-decoration:none!important;display:inline-flex;align-items:center;gap:.4rem}
.cv-page-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.cv-page-header-title{font-size:1.65rem;font-weight:700;margin:0}
.cv-bottom-nav{display:flex;position:fixed;bottom:0;left:0;right:0;height:68px;background:#fff;border-top:1px solid #D8E3F0;z-index:1030}
.cv-bottom-nav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:.68rem;color:#64748B;text-decoration:none!important}
.cv-bottom-nav-item.active{color:#0F4CFF}
@media(min-width:992px){.cv-bottom-nav{display:none!important}}
@media(max-width:991px){.cv-main{margin-left:0!important}.cv-sidebar{transform:translateX(-100%)}.cv-sidebar.cv-sidebar-open{transform:translateX(0)}}
</style>
{$footeroutput}
<script src="/templates/centralvix-client/assets/js/centralvix-client.min.js?v=1.3.1"></script>
<!-- cvix-footer-marker:v1.3.1 -->
<script>(function(){if(typeof jQuery==='undefined')return;jQuery(function(){jQuery('.table-list[id]').removeClass('w-hidden').show();jQuery('#tableLoading').hide();jQuery('.modal:not(.show)').hide();});})();</script>
</body>
</html>
