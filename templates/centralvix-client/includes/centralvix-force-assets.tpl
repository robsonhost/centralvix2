{* CentralVix — carrega CSS/JS mesmo com cache Smarty quebrado (HTML5: link no body funciona) *}
<!-- CVIX-FORCE-ASSETS-v1.2.4 -->
<link rel="stylesheet" href="/templates/centralvix-client/css/all.min.css?v=1.2.4">
<link rel="stylesheet" href="/templates/centralvix-client/css/theme.min.css?v=1.2.4">
<link rel="stylesheet" href="/templates/centralvix-client/assets/css/centralvix-client.bundle.css?v=1.2.4">
<link rel="stylesheet" href="/assets/fonts/css/fontawesome.min.css">
<link rel="stylesheet" href="/assets/fonts/css/fontawesome-solid.min.css">
<script src="/templates/centralvix-client/js/scripts.min.js?v=1.2.4"></script>
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
