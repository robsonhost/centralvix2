(function(){
'use strict';
var c=window.CVIX_STREAM||{};
function toast(m,ok){var e=document.createElement('div');e.className='cvix-st-toast '+(ok?'ok':'err');e.textContent=m;document.body.appendChild(e);setTimeout(function(){e.remove();},3500);}
function post(url,data,cb){var fd=new FormData();Object.keys(data).forEach(function(k){fd.append(k,data[k]);});fetch(url,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(cb).catch(function(){toast('Erro de comunicação',false);});}
var sf=document.getElementById('cvixServerForm');
if(sf){sf.addEventListener('submit',function(e){e.preventDefault();var d={token:c.csrf};sf.querySelectorAll('input,select,textarea').forEach(function(el){if(!el.name)return;if(el.type==='checkbox'){if(el.checked)d[el.name]=el.value||'1';}else d[el.name]=el.value;});post(c.ajaxSaveServer,d,function(j){toast(j.message||'',j.success);if(j.success)setTimeout(function(){location.href=c.moduleLink+'&tab=servers&edit='+(j.id||'');},600);});});}
document.querySelectorAll('.cvix-test-server').forEach(function(btn){btn.addEventListener('click',function(){post(c.ajaxServerTest,{token:c.csrf,server_id:btn.getAttribute('data-id')},function(j){var box=document.getElementById('cvixServerTestResult');if(box)box.innerHTML='<p class="cvix-st-badge '+(j.success?'ok':'muted')+'">'+(j.message||'')+'</p>';toast(j.message||'',j.success);});});});
document.querySelectorAll('.cvix-account-action').forEach(function(btn){btn.addEventListener('click',function(){if(!confirm('Executar '+btn.getAttribute('data-action')+'?'))return;post(c.ajaxAccountAction,{token:c.csrf,account_id:btn.getAttribute('data-id'),action:btn.getAttribute('data-action')},function(j){toast(j.message||'',j.success);if(j.success)location.reload();});});});
document.querySelectorAll('.cvix-fetch-stats').forEach(function(btn){btn.addEventListener('click',function(){fetch(c.ajaxStats+'&account_id='+btn.getAttribute('data-id')).then(function(r){return r.json();}).then(function(j){alert(j.success?'Ouvintes: '+(j.live&&j.live.listeners)+'\nPico 7d: '+(j.summary&&j.summary.peak_listeners):j.message);});});});
var col=document.getElementById('cvixCollectStats');
if(col){col.addEventListener('click',function(){toast('Use cron WHMCS ou acesse stats por conta.',true);});}
})();
