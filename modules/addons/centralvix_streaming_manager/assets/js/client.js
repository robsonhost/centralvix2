(function(){
'use strict';
var c=window.CVIX_STREAM_CLIENT||{};
function toast(m,ok){alert(m);}
function post(url,data,cb){var fd=new FormData();Object.keys(data).forEach(function(k){fd.append(k,data[k]);});fetch(url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(cb).catch(function(){toast('Erro',false);});}
document.querySelectorAll('.cvix-client-action').forEach(function(btn){
btn.addEventListener('click',function(){
var act=btn.getAttribute('data-action');
if(act==='change_password'&&!confirm('Gerar nova senha no servidor?'))return;
post(c.ajaxAccountAction,{token:c.csrf,account_id:btn.getAttribute('data-account'),action:act},function(j){toast(j.message||'',j.success);if(j.success)location.reload();});
});});
})();
