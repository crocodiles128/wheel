<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sorteio | Equipe</title>
<link rel="stylesheet" href="siemens.css">
<style>
.staff{width:min(100%,1000px);min-width:0;margin:auto;padding:clamp(14px,4vw,32px)}.staff .card{width:100%;min-width:0;max-width:100%;overflow:hidden}.row{display:flex;gap:12px;flex-wrap:wrap}.row button{width:auto;min-width:170px;padding-inline:20px}.secondary{background:var(--violet)}
.crate-window{position:relative;display:flex;width:100%;height:clamp(140px,24vw,220px);margin:26px 0 12px;align-items:center;overflow:hidden;border:6px solid #37224e;border-radius:14px;background:linear-gradient(180deg,#251638,#493066 50%,#251638);box-shadow:inset 0 0 25px #120b1f8c,0 10px 22px #24123e20}.crate-window:before,.crate-window:after{position:absolute;z-index:2;top:0;bottom:0;width:36px;content:'';pointer-events:none}.crate-window:before{left:0;background:linear-gradient(90deg,#251638,transparent)}.crate-window:after{right:0;background:linear-gradient(270deg,#251638,transparent)}
.crate-track{display:flex;width:max-content;height:100%;flex:0 0 auto;align-items:center;gap:10px;padding:0 12px;transform:translateX(0);will-change:transform}.crate-cell{display:grid;width:clamp(64px,8vw,98px);height:clamp(82px,14vw,125px);flex:none;place-items:center;overflow:hidden;white-space:nowrap;border:2px solid #a98ad4;border-radius:9px;background:linear-gradient(145deg,#f6f0ff,#d4c1f1);box-shadow:inset 0 0 0 4px #fff8;color:#30213f;font:800 clamp(15px,1.7vw,21px) Arial,sans-serif;font-variant-numeric:tabular-nums;letter-spacing:-.04em}.crate-cell.is-winner{border-color:#d8ff89;background:linear-gradient(145deg,#e9ffb8,#c7f36b);box-shadow:0 0 24px #c7f36b9c,inset 0 0 0 4px #fff9;color:#24123e}.crate-marker{position:absolute;z-index:3;top:0;bottom:0;left:50%;width:3px;transform:translateX(-50%);background:#c7f36b;box-shadow:0 0 13px #c7f36b}.winner-label{text-align:center;color:#586568;font-weight:700}.winner-label strong{display:block;color:var(--teal);font-size:clamp(32px,8vw,56px);margin-top:6px}#error{color:#a22;margin-top:12px;line-height:1.5}table{width:100%;border-collapse:collapse;margin-top:20px}td,th{text-align:left;padding:10px;border-bottom:1px solid #e4e9e9}
@media(max-width:560px){.staff{padding:14px}.staff .card{padding:25px 18px}.row{display:grid;grid-template-columns:1fr}.row button{width:100%}.crate-window{border-width:4px;height:145px;margin:20px 0 10px;min-width:0;max-width:100%}.crate-track{gap:7px;padding:0 9px}.crate-cell{width:clamp(56px,16vw,76px);height:92px}table{font-size:13px}}
</style>
</head>
<body>
<header><a href="inscrever.php" class="brand">SIEMENS ENERGY <span>EQUIPE</span></a><a href="inscrever.php" class="staff-link">Página de inscrição</a></header>
<main><section class="staff">
<div class="card" id="loginBox"><div class="eyebrow">ÁREA RESTRITA</div><h1>Acesso dos colaboradores</h1><form id="login"><label>Nome de usuário</label><input id="nome" required><label>Senha</label><input id="senha" type="password" required><button>Entrar</button></form><div id="error"></div></div>
<div class="card" id="panel" hidden><div class="eyebrow">MODO CS • PAINEL DO COLABORADOR</div><h1>Sorteio</h1><div class="row"><button id="openScreen" class="secondary">Abrir tela do telão</button><button id="draw">Sortear próximo número</button><button id="close" class="secondary">Finalizar inscrições</button><button id="logout" class="secondary">Sair</button></div><p>A tela do telão mostra somente a régua e o número sorteado.</p><p id="status"></p><table><thead><tr><th>Peito</th><th>Sorteado</th></tr></thead><tbody id="rows"></tbody></table></div>
</section></main>
<script>
const call=(a,d={})=>fetch('api.php?action='+a,{method:'GET',headers:{'Content-Type':'application/json'},...(a==='load'?{}:{method:'POST',body:JSON.stringify(d)})}).then(async r=>{let x=await r.json();if(!r.ok)throw Error(x.error||'Erro');return x});const msg=e=>document.getElementById('error').textContent=e;
async function panel(){document.getElementById('loginBox').hidden=true;document.getElementById('panel').hidden=false;await load()}
async function load(){try{let x=await call('load');document.getElementById('rows').innerHTML=x.entries.map(r=>`<tr><td>${String(r.numero).padStart(2,'0')}</td><td>${Number(r.sorteado)?'Sim':'Não'}</td></tr>`).join('')}catch(e){msg(e.message)}}
const drawChannel=new BroadcastChannel('sorteio-cs-display');
document.getElementById('openScreen').onclick=()=>{const screen=window.open('telao.php','sorteio-telao','popup,width=1280,height=800');if(screen)screen.focus()};
document.getElementById('login').onsubmit=async e=>{e.preventDefault();try{await call('login',{nome:document.getElementById('nome').value,senha:document.getElementById('senha').value});await panel()}catch(x){msg(x.message)}};
document.getElementById('draw').onclick=async()=>{const button=document.getElementById('draw');button.disabled=true;msg('');try{const before=await call('load'),result=await call('draw'),winner=Number(result.winner);drawChannel.postMessage({type:'draw',winner,entries:before.entries});await load()}catch(e){msg(e.message)}finally{button.disabled=false}};
document.getElementById('close').onclick=async()=>{try{await call('close',{closed:true});document.getElementById('status').textContent='Inscrições finalizadas.'}catch(e){msg(e.message)}};document.getElementById('logout').onclick=async()=>{await call('logout');location.reload()};
</script></body></html>
