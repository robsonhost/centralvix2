(function(){'use strict';var c=window.CVIX_AUTODJ||{};function toast(m,ok){alert(m);}
function post(url,fd,cb){fetch(url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(cb);}
var sf=document.getElementById('cvixServerForm');if(sf){sf.addEventListener('submit',function(e){e.preventDefault();var fd=new FormData(sf);fd.append('token',c.csrf);post(c.ajaxSaveServer||'save_server.php',fd,function(j){toast(j.message,j.success);if(j.success)location.reload();});});}
document.querySelectorAll('.cvix-delete-media').forEach(function(b){b.addEventListener('click',function(){if(!confirm('Excluir?'))return;var fd=new FormData();fd.append('token',c.csrf);fd.append('media_id',b.getAttribute('data-id'));post(c.ajaxDelete,fd,function(j){toast(j.message,j.success);if(j.success)location.reload();});});});
})();
