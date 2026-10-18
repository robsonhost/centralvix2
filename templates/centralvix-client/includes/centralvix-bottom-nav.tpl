<nav class="cv-bottom-nav d-lg-none" aria-label="Navegação principal mobile">
    <a href="{$WEB_ROOT}/clientarea.php" class="cv-bottom-nav-item{if $templatefile == 'clientarea' || $templatefile == 'clientareahome'} active{/if}">
        <i class="fas fa-home"></i>
        <span>Início</span>
    </a>
    <a href="{$WEB_ROOT}/clientarea.php?action=services" class="cv-bottom-nav-item{if $templatefile == 'clientareaproducts' || $templatefile == 'clientareaproductdetails'} active{/if}">
        <i class="fas fa-server"></i>
        <span>Serviços</span>
    </a>
    <a href="{$WEB_ROOT}/clientarea.php?action=invoices" class="cv-bottom-nav-item{if $templatefile == 'clientareainvoices' || $templatefile == 'viewinvoice'} active{/if}">
        <i class="fas fa-file-invoice-dollar"></i>
        <span>Financeiro</span>
    </a>
    <a href="{$WEB_ROOT}/supporttickets.php" class="cv-bottom-nav-item{if $templatefile == 'supportticketslist' || $templatefile == 'viewticket'} active{/if}">
        <i class="fas fa-life-ring"></i>
        <span>Suporte</span>
    </a>
    <button type="button" class="cv-bottom-nav-item" id="cv-mobile-menu-bottom" aria-label="Menu">
        <i class="fas fa-ellipsis-h"></i>
        <span>Menu</span>
    </button>
</nav>
