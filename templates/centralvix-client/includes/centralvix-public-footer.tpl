<footer id="footer" class="footer cv-public-footer">
    <div class="container">
        <ul class="nav justify-content-center mb-4">
            {if $acceptTOS}
            <li class="nav-item"><a class="nav-link" href="{$tosURL}" target="_blank">Termos</a></li>
            {/if}
            <li class="nav-item"><a class="nav-link" href="{$WEB_ROOT}/contact.php">{lang key='contactus'}</a></li>
            <li class="nav-item"><a class="nav-link" href="{$WEB_ROOT}/supporttickets.php">{lang key='navtickets'}</a></li>
        </ul>
        <p class="copyright mb-0 text-center">
            {lang key="copyrightFooterNotice" year=$date_year company=$companyname}
        </p>
    </div>
</footer>
