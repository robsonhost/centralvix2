<footer class="cv-app-footer">
    <div class="cv-app-footer-inner">
        <div class="cv-app-footer-brand">
            <img src="{$WEB_ROOT}/templates/{$template}/assets/img/logo-dark.svg" alt="CentralVix" class="cv-logo cv-logo-sm">
        </div>
        <ul class="cv-app-footer-links">
            {if $acceptTOS}
            <li><a href="{$tosURL}" target="_blank" rel="noopener">Termos</a></li>
            {/if}
            <li><a href="{$WEB_ROOT}/contact.php">{lang key='contactus'}</a></li>
            <li><a href="{$WEB_ROOT}/supporttickets.php">{lang key='navtickets'}</a></li>
        </ul>
        <p class="cv-app-footer-copy">
            {lang key="copyrightFooterNotice" year=$date_year company=$companyname}
        </p>
    </div>
</footer>

{* WhatsApp flutuante — número via data attribute (configurável por hook/addon) *}
<div class="cv-whatsapp-wrap" id="cv-whatsapp-wrap" data-whatsapp="" hidden>
    <a href="#" class="cv-whatsapp-btn" id="cv-whatsapp-btn" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>
