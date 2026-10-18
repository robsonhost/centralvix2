(function(){'use strict';var c=window.CVIX_AUTODJ_CLIENT||{};
function post(url,fd,cb){fetch(url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(cb);}
function fdFrom(form){var fd=new FormData(form);fd.append('token',c.csrf);return fd;}
var uf=document.getElementById('cvixUploadForm');if(uf){uf.addEventListener('submit',function(e){e.preventDefault();post(c.ajaxUpload,fdFrom(uf),function(j){alert(j.message);if(j.success)location.reload();});});}
document.querySelectorAll('.cvix-delete-media').forEach(function(b){b.addEventListener('click',function(){if(!confirm('Excluir arquivo?'))return;var fd=new FormData();fd.append('token',c.csrf);fd.append('media_id',b.getAttribute('data-id'));post(c.ajaxDelete,fd,function(j){alert(j.message);if(j.success)location.reload();});});});
var pf=document.getElementById('cvixPlaylistForm');if(pf){pf.addEventListener('submit',function(e){e.preventDefault();post(c.ajaxPlaylist,fdFrom(pf),function(j){alert(j.message);if(j.success)location.reload();});});}
var sf=document.getElementById('cvixScheduleForm');if(sf){sf.addEventListener('submit',function(e){e.preventDefault();post(c.ajaxSchedule,fdFrom(sf),function(j){var box=document.getElementById('cvixConflictAlert');if(j.conflicts&&j.conflicts.length&&box){box.classList.remove('hidden');box.innerHTML='<strong>Conflitos:</strong> '+j.conflicts.map(function(x){return x.title+' ('+x.time_start+'-'+x.time_end+')';}).join(', ');}alert(j.message);if(j.success)location.reload();});});}
})();
