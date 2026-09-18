{{--
    CHATWOOT CHAT MODAL
    Injected via: lead-tools-panel.blade.php (admin.leads.view.stages.after)
    API Proxy:    /admin/juridico/atendimento/leads/{lead}/chatwoot/*
    Theming:      JS detects html.dark class (Krayin standard) and applies styles inline.
--}}
@php
    $convId  = $lead->chatwoot_conversation_id ?? null;
    $leadId  = $lead->id;
    try {
        $cwtConfig    = \SuiteZap\LawFirm\SaaS\Services\MotherShipService::getChatwootConfig();
        $cwtBase      = rtrim($cwtConfig['base_url'] ?? '', '/');
        $cwtAccountId = $cwtConfig['account_id'] ?? '';
    } catch (\Throwable $e) {
        $cwtBase = ''; $cwtAccountId = '';
    }
    $chatwootConvUrl = ($convId && $cwtBase)
        ? "{$cwtBase}/app/accounts/{$cwtAccountId}/conversations/{$convId}"
        : null;
    $urlMessages    = route('admin.lawfirm.chatwoot.lead.messages',        ['lead' => $leadId]);
    $urlSend        = route('admin.lawfirm.chatwoot.lead.send',             ['lead' => $leadId]);
    $urlCanned      = route('admin.lawfirm.chatwoot.lead.canned_responses', ['lead' => $leadId]);
    $urlMacros      = route('admin.lawfirm.chatwoot.lead.macros',           ['lead' => $leadId]);
    $urlMacroRunTpl = route('admin.lawfirm.chatwoot.lead.macro_run',        ['lead' => $leadId, 'macroId' => '__MACRO_ID__']);
@endphp

{{-- MODAL ROOT — always hidden at start --}}
<div id="lf-chatwoot-modal" role="dialog" aria-modal="true" aria-labelledby="lf-cwt-title"
     style="display:none;position:fixed;inset:0;z-index:9999;">
</div>

@if($convId)
<script>
(function(){
'use strict';

/* ── Theme detection ─────────────────────────────────────────────────────── */
function isDark(){return document.documentElement.classList.contains('dark');}

var T = {
    get bg()          { return isDark()?'#111827':'#ffffff'; },
    get bgSubtle()    { return isDark()?'#1f2937':'#f9fafb'; },
    get bgMuted()     { return isDark()?'#374151':'#f3f4f6'; },
    get border()      { return isDark()?'#374151':'#e5e7eb'; },
    get borderMuted() { return isDark()?'#4b5563':'#d1d5db'; },
    get text()        { return isDark()?'#f3f4f6':'#111827'; },
    get textMuted()   { return isDark()?'#9ca3af':'#6b7280'; },
    get textSubtle()  { return isDark()?'#6b7280':'#9ca3af'; },
    get backdrop()    { return isDark()?'rgba(0,0,0,.65)':'rgba(0,0,0,.4)'; },
    get shadow()      { return isDark()?'0 20px 60px rgba(0,0,0,.5)':'0 20px 60px rgba(0,0,0,.15)'; },
    /* Bubbles:
       type=1 outgoing = agent/bot → LEFT (azul/roxo)
       type=0 incoming = lead/contact → RIGHT (verde/cinza)
    */
    get agentBg()     { return isDark()?'#1e3a5f':'#dbeafe'; },   /* azul */
    get agentText()   { return isDark()?'#bfdbfe':'#1e3a5f'; },
    get leadBg()      { return isDark()?'#14532d':'#dcfce7'; },    /* verde */
    get leadText()    { return isDark()?'#bbf7d0':'#14532d'; },
    get privBg()      { return isDark()?'#3b2a1a':'#fef3c7'; },
    get privText()    { return isDark()?'#fcd34d':'#78350f'; },
    get privBorder()  { return isDark()?'#78350f':'#fbbf24'; },
    get inputBg()     { return isDark()?'#111827':'#f9fafb'; },
    get inputText()   { return isDark()?'#f3f4f6':'#111827'; },
    get inputBorder() { return isDark()?'#374151':'#d1d5db'; },
    get btnBg()       { return '#7c3aed'; },
    get btnText()     { return '#ffffff'; },
    get accent()      { return isDark()?'#a78bfa':'#7c3aed'; },
    get tabNoteActive(){ return isDark()
        ?{bg:'#3b2a1a',text:'#fcd34d',border:'#78350f'}
        :{bg:'#fef3c7',text:'#92400e',border:'#fbbf24'}; },
};

/* ── API ────────────────────────────────────────────────────────────────── */
/* Krayin uses cookie-based XSRF (not meta tag). Read XSRF-TOKEN cookie
   and send it as X-XSRF-TOKEN on every request (same as Axios config). */
function getXsrfToken(){
    var match=document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
    return match?decodeURIComponent(match[1]):'';
}
var URL_MSG    =@json($urlMessages);
var URL_SEND   =@json($urlSend);
var URL_CANNED =@json($urlCanned);
var URL_MACROS =@json($urlMacros);
var URL_MACRO_TPL=@json($urlMacroRunTpl);
var CONV_URL = @json($chatwootConvUrl);

var currentMode='reply';
var macrosLoaded=false;
var macrosOpen=false;
var pollTimer=null;

function el(id){return document.getElementById(id);}

function setStatus(msg,color){
    var s=el('lf-cwt-status');
    if(!s)return;
    s.textContent=msg;
    s.style.color=color||T.textSubtle;
}

function esc(s){
    return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmt(ts){
    if(!ts)return'';
    var d=new Date(typeof ts==='number'?ts*1000:ts);
    return d.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'});
}

function apiFetch(url,opts){
    opts=opts||{};
    return fetch(url,Object.assign({},opts,{
        credentials:'same-origin',
        headers:Object.assign({
            'Content-Type':'application/json',
            'Accept':'application/json',
            'X-Requested-With':'XMLHttpRequest',
            'X-XSRF-TOKEN':getXsrfToken()
        },opts.headers||{})
    })).then(function(r){if(!r.ok)return r.json().then(function(e){return Promise.reject(e);});return r.json();});
}

/* ── Build modal HTML ───────────────────────────────────────────────────── */
function buildModal(){
    var root=el('lf-chatwoot-modal');
    root.innerHTML='';

    /* Backdrop */
    var bd=document.createElement('div');
    bd.id='lf-cwt-backdrop';
    bd.style.cssText='position:absolute;inset:0;background:'+T.backdrop+';backdrop-filter:blur(2px);cursor:pointer;';
    bd.addEventListener('click',function(){window.lfChatwootModal.close();});
    root.appendChild(bd);

    /* Wrapper */
    var wrap=document.createElement('div');
    wrap.style.cssText='position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none;';

    /* Dialog */
    var dlg=document.createElement('div');
    dlg.id='lf-cwt-dialog';
    dlg.style.cssText=[
        'pointer-events:all',
        'background:'+T.bg,
        'border:1px solid '+T.border,
        'border-radius:14px',
        'width:100%',
        'max-width:800px',
        'max-height:92vh',
        'display:flex',
        'flex-direction:column',
        'box-shadow:'+T.shadow,
        'overflow:hidden',
    ].join(';');

    dlg.innerHTML = buildInnerHTML();
    wrap.appendChild(dlg);
    root.appendChild(wrap);

    wireEvents();
}

function buildInnerHTML(){
    var convId  = @json($convId);
    var convLabel = convId ? ('&#x1F4AC; Conversa #' + convId) : 'Sem conversa vinculada';
    var extLink = CONV_URL
        ? '<a href="'+esc(CONV_URL)+'" target="_blank" title="'+esc(CONV_URL)+'" style="display:inline-flex;align-items:center;gap:.3rem;font-size:.71rem;color:'+T.accent+';text-decoration:none;padding:.25rem .5rem;border:1px solid '+T.borderMuted+';border-radius:5px;">&#x2197; Abrir no Chatwoot</a>'
        : '';

    return [
        /* Header */
        '<div id="lf-cwt-header" style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-bottom:1px solid '+T.border+';background:'+T.bgSubtle+';flex-shrink:0;">',
          '<div style="display:flex;align-items:center;gap:.6rem;">',
            '<span style="font-size:1.15rem;">&#x1F4AC;</span>',
            '<div>',
              '<h3 id="lf-cwt-title" style="margin:0;font-size:.88rem;font-weight:700;color:'+T.text+';">Chat do Lead</h3>',
              '<p id="lf-cwt-conv-label" style="margin:0;font-size:.71rem;color:'+T.textMuted+';">'+convLabel+'</p>',
            '</div>',
          '</div>',
          '<div style="display:flex;align-items:center;gap:.5rem;">',
            extLink,
            '<button id="lf-cwt-close" style="background:none;border:none;cursor:pointer;color:'+T.textMuted+';font-size:1rem;padding:.25rem .4rem;border-radius:5px;line-height:1;">&#x2715;</button>',
          '</div>',
        '</div>',

        /* Toolbar */
        '<div id="lf-cwt-toolbar" style="display:flex;align-items:center;gap:.5rem;padding:.4rem .75rem;border-bottom:1px solid '+T.border+';background:'+T.bgSubtle+';flex-shrink:0;flex-wrap:wrap;">',
          '<button id="lf-cwt-btn-macros" style="'+btnStyle()+'" >&#x26A1; Macros</button>',
          '<button id="lf-cwt-btn-refresh" style="'+btnStyle()+'">&#x21BA; Atualizar</button>',
          '<span id="lf-cwt-status" style="margin-left:auto;font-size:.7rem;color:'+T.textSubtle+';"></span>',
        '</div>',

        /* Macros drawer */
        '<div id="lf-cwt-macros-drawer" style="display:none;max-height:150px;overflow-y:auto;border-bottom:1px solid '+T.border+';background:'+T.bgSubtle+';padding:.5rem .75rem;flex-shrink:0;">',
          '<p style="margin:0 0 .4rem;font-size:.7rem;font-weight:700;color:'+T.textMuted+';text-transform:uppercase;letter-spacing:.04em;">Macros</p>',
          '<div id="lf-cwt-macros-list" style="display:flex;flex-wrap:wrap;gap:.35rem;"><span style="color:'+T.textSubtle+';font-size:.78rem;">Carregando...</span></div>',
        '</div>',

        /* Messages area */
        '<div id="lf-cwt-messages" style="flex:1;overflow-y:auto;padding:.75rem 1rem;display:flex;flex-direction:column;gap:.55rem;background:'+T.bg+';min-height:200px;">',
          '<div style="text-align:center;color:'+T.textSubtle+';font-size:.8rem;padding:1rem;">Carregando mensagens...</div>',
        '</div>',

        /* Canned picker */
        '<div id="lf-cwt-canned-picker" style="display:none;max-height:140px;overflow-y:auto;border-top:1px solid '+T.border+';background:'+T.bgSubtle+';padding:.4rem .75rem;flex-shrink:0;">',
          '<p style="margin:0 0 .3rem;font-size:.7rem;font-weight:700;color:'+T.textMuted+';text-transform:uppercase;">Respostas Rapidas</p>',
          '<div id="lf-cwt-canned-list" style="display:flex;flex-direction:column;gap:.2rem;"></div>',
        '</div>',

        /* Compose */
        '<div style="flex-shrink:0;border-top:1px solid '+T.border+';background:'+T.bgSubtle+';padding:.55rem .75rem;">',
          '<div style="display:flex;gap:.4rem;margin-bottom:.4rem;">',
            '<button id="lf-cwt-tab-reply" style="'+tabReplyActiveStyle()+'">&#x21A9; Responder</button>',
            '<button id="lf-cwt-tab-note"  style="'+tabInactiveStyle()+'">&#x1F512; Nota Privada</button>',
          '</div>',
          '<div style="display:flex;align-items:flex-end;gap:.5rem;">',
            '<textarea id="lf-cwt-compose" rows="3" placeholder="Digite uma mensagem... (use / para respostas rapidas)" style="flex:1;box-sizing:border-box;background:'+T.inputBg+';color:'+T.inputText+';border:1px solid '+T.inputBorder+';border-radius:8px;padding:.45rem .7rem;font-size:.82rem;resize:none;font-family:inherit;transition:border-color .15s;"></textarea>',
            '<button id="lf-cwt-send-btn" style="flex-shrink:0;background:'+T.btnBg+';color:'+T.btnText+';border:none;border-radius:8px;padding:.5rem .9rem;font-size:.82rem;font-weight:700;cursor:pointer;white-space:nowrap;min-height:72px;">Enviar &#x2191;</button>',
          '</div>',
        '</div>',
    ].join('');
}

function btnStyle(){
    return 'font-size:.74rem;font-weight:600;padding:.28rem .6rem;border-radius:6px;border:1px solid '+T.borderMuted+';background:'+T.bgMuted+';color:'+T.textMuted+';cursor:pointer;';
}

function tabReplyActiveStyle(){
    return 'font-size:.72rem;font-weight:600;padding:.22rem .6rem;border-radius:5px;cursor:pointer;border:1px solid '+T.accent+';background:'+T.bgMuted+';color:'+T.accent+';';
}

function tabNoteActiveStyle(){
    var n=T.tabNoteActive;
    return 'font-size:.72rem;font-weight:600;padding:.22rem .6rem;border-radius:5px;cursor:pointer;border:1px solid '+n.border+';background:'+n.bg+';color:'+n.text+';';
}

function tabInactiveStyle(){
    return 'font-size:.72rem;font-weight:600;padding:.22rem .6rem;border-radius:5px;cursor:pointer;border:1px solid '+T.border+';background:transparent;color:'+T.textMuted+';';
}

/* ── Render messages ─────────────────────────────────────────────────────── */
/*
   Alinhamento (perspectiva CRM):
     Agente / Bot (type=1, outgoing) → ESQUERDA (quem atendeu)
     Lead  / Contato (type=0, incoming) → DIREITA (quem enviou)
     Nota privada → ESQUERDA, fundo amarelado
*/
function renderMessages(msgs){
    var box=el('lf-cwt-messages');
    if(!box)return;
    if(!msgs||!msgs.length){
        box.innerHTML='<div style="text-align:center;color:'+T.textSubtle+';font-size:.8rem;padding:2rem;">Nenhuma mensagem nesta conversa.</div>';
        return;
    }
    box.innerHTML=msgs.map(function(m){
        var type=m.message_type; // 0=incoming/lead, 1=outgoing/agent, 2=activity
        var isPriv=m.private===true;
        var content=m.content??'';
        var sender=m.sender&&m.sender.name?m.sender.name:(type===1?'Assistente':'Lead');
        var time=fmt(m.created_at);

        /* Activity / system event */
        if(type===2||type===3){
            return '<div style="text-align:center;font-size:.7rem;padding:.2rem 0;color:'+T.textSubtle+';font-style:italic;">'+esc(content)+'</div>';
        }

        /* Nota privada — esquerda, fundo amarelo */
        if(isPriv){
            return '<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-start;max-width:78%;align-items:flex-start;">'
                +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">&#x1F512; Nota privada</span>'
                +'<div style="background:'+T.privBg+';color:'+T.privText+';border:1px dashed '+T.privBorder+';border-radius:10px;padding:.4rem .7rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;font-style:italic;">'+esc(content)+'</div>'
                +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
                +'</div>';
        }

        /* type=1 outgoing = Agente → ESQUERDA */
        if(type===1){
            return '<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-start;max-width:78%;align-items:flex-start;">'
                +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">&#x1F916; '+esc(sender)+'</span>'
                +'<div style="background:'+T.agentBg+';color:'+T.agentText+';border-radius:12px;border-bottom-left-radius:3px;padding:.42rem .72rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;">'+esc(content)+'</div>'
                +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
                +'</div>';
        }

        /* type=0 incoming = Lead → DIREITA */
        return '<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-end;max-width:78%;align-items:flex-end;">'
            +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">&#x1F464; '+esc(sender)+'</span>'
            +'<div style="background:'+T.leadBg+';color:'+T.leadText+';border-radius:12px;border-bottom-right-radius:3px;padding:.42rem .72rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;">'+esc(content)+'</div>'
            +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
            +'</div>';
    }).join('');
    box.scrollTop=box.scrollHeight;
}

/* ── Wire events ─────────────────────────────────────────────────────────── */
function wireEvents(){
    var close=el('lf-cwt-close');
    if(close)close.addEventListener('click',function(){window.lfChatwootModal.close();});

    var btnMacros=el('lf-cwt-btn-macros');
    if(btnMacros)btnMacros.addEventListener('click',function(){window.lfChatwootModal.toggleMacros();});

    var btnRefresh=el('lf-cwt-btn-refresh');
    if(btnRefresh)btnRefresh.addEventListener('click',function(){loadMessages();});

    var tabR=el('lf-cwt-tab-reply');
    if(tabR)tabR.addEventListener('click',function(){window.lfChatwootModal.setMode('reply');});
    var tabN=el('lf-cwt-tab-note');
    if(tabN)tabN.addEventListener('click',function(){window.lfChatwootModal.setMode('note');});

    var sendBtn=el('lf-cwt-send-btn');
    if(sendBtn)sendBtn.addEventListener('click',function(){send();});

    var ta=el('lf-cwt-compose');
    if(ta){
        ta.addEventListener('keydown',function(e){
            if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();send();}
        });
        ta.addEventListener('input',function(){
            var v=ta.value;
            if(v.startsWith('/')&&v.length>=2)loadCanned(v.slice(1));
            else{var p=el('lf-cwt-canned-picker');if(p)p.style.display='none';}
        });
    }
}

/* ── Data loaders ────────────────────────────────────────────────────────── */
function loadMessages(silent){
    if(!silent)setStatus('Atualizando...', T.accent);
    return apiFetch(URL_MSG).then(function(d){
        renderMessages(d.messages??[]);
        /* Update subtitle with the conv ID the API actually returned */
        var lbl=el('lf-cwt-conv-label');
        if(lbl && d.conversation_id){
            lbl.textContent='&#x1F4AC; Conversa #'+d.conversation_id+' \u2022 '+d.messages.length+' mensagem(ns)';
            lbl.innerHTML='&#x1F4AC; Conversa #'+d.conversation_id+' &bull; '+(d.messages??[]).length+' mensagem(ns)';
        }
        if(!silent)setStatus('Atualizado '+new Date().toLocaleTimeString('pt-BR'));
    }).catch(function(err){
        if(!silent)setStatus('Erro ao buscar mensagens','#ef4444');
        console.error('[CWT] loadMessages error', err);
    });
}

function loadMacros(){
    if(macrosLoaded)return;
    var ml=el('lf-cwt-macros-list');
    if(ml)ml.innerHTML='<span style="color:'+T.textSubtle+';font-size:.78rem;">Carregando...</span>';
    apiFetch(URL_MACROS).then(function(d){
        var macros=d.macros??[];macrosLoaded=true;
        if(!ml)return;
        if(!macros.length){ml.innerHTML='<span style="color:'+T.textSubtle+';font-size:.78rem;">Nenhuma macro.</span>';return;}
        ml.innerHTML=macros.map(function(m){
            return '<button onclick="window.lfChatwootModal.runMacro('+m.id+',\''+esc(m.name)+'\')" style="font-size:.72rem;padding:.22rem .55rem;border-radius:5px;border:1px solid '+T.borderMuted+';background:'+T.bgMuted+';color:'+T.accent+';cursor:pointer;white-space:nowrap;">&#x26A1; '+esc(m.name)+'</button>';
        }).join('');
    }).catch(function(){
        if(ml)ml.innerHTML='<span style="color:#ef4444;font-size:.78rem;">Erro ao carregar macros.</span>';
    });
}

function runMacro(macroId,macroName){
    setStatus('Executando "'+macroName+'"...','#f59e0b');
    apiFetch(URL_MACRO_TPL.replace('__MACRO_ID__',macroId),{method:'POST'}).then(function(){
        setStatus('Macro executada');
        setTimeout(function(){loadMessages(true);},1500);
    }).catch(function(){setStatus('Erro ao executar macro','#ef4444');});
}

function loadCanned(q){
    apiFetch(URL_CANNED+(q?'?q='+encodeURIComponent(q):'')).then(function(d){
        var items=d.canned_responses??[];
        var picker=el('lf-cwt-canned-picker');
        if(!picker)return;
        if(!items.length){picker.style.display='none';return;}
        picker.style.display='block';
        var cl=el('lf-cwt-canned-list');
        if(cl)cl.innerHTML=items.slice(0,8).map(function(c){
            var preview=c.content.length>90?c.content.substring(0,90)+'...':c.content;
            return '<div onclick="window.lfChatwootModal.applyCanned(\''+esc(c.content).replace(/'/g,'&#39;')+'\')" '
                +'style="padding:.3rem .45rem;border-radius:5px;cursor:pointer;font-size:.78rem;color:'+T.text+';display:flex;align-items:baseline;gap:.45rem;transition:background .1s;" '
                +'onmouseover="this.style.background=\''+T.bgMuted+'\'" onmouseout="this.style.background=\'\'">'
                +'<strong style="color:'+T.accent+';flex-shrink:0;">/'+esc(c.short_code)+'</strong>'
                +'<span style="color:'+T.textMuted+';">'+esc(preview)+'</span>'
                +'</div>';
        }).join('');
    }).catch(function(){var p=el('lf-cwt-canned-picker');if(p)p.style.display='none';});
}

function applyCanned(content){
    var ta=el('lf-cwt-compose');if(ta){ta.value=content;ta.focus();}
    var p=el('lf-cwt-canned-picker');if(p)p.style.display='none';
}

function send(){
    var ta=el('lf-cwt-compose');
    var msg=(ta&&ta.value?ta.value:'').trim();
    if(!msg)return;
    var btn=el('lf-cwt-send-btn');
    if(btn){btn.disabled=true;btn.style.opacity='.5';}
    setStatus('Enviando...', T.accent);
    apiFetch(URL_SEND,{method:'POST',body:JSON.stringify({message:msg,private:currentMode==='note'})})
    .then(function(){
        if(ta)ta.value='';
        var p=el('lf-cwt-canned-picker');if(p)p.style.display='none';
        setStatus('Enviado');
        setTimeout(function(){loadMessages(true);},600);
    }).catch(function(err){
        setStatus((err&&err.error)||'Erro ao enviar','#ef4444');
    }).finally(function(){
        if(btn){btn.disabled=false;btn.style.opacity='1';}
    });
}

function setMode(mode){
    currentMode=mode;
    var ta=el('lf-cwt-compose');
    var r=el('lf-cwt-tab-reply');
    var n=el('lf-cwt-tab-note');
    if(mode==='reply'){
        if(r)r.style.cssText=tabReplyActiveStyle();
        if(n)n.style.cssText=tabInactiveStyle();
        if(ta)ta.placeholder='Digite uma mensagem... (use / para respostas rapidas)';
    }else{
        if(n)n.style.cssText=tabNoteActiveStyle();
        if(r)r.style.cssText=tabInactiveStyle();
        if(ta)ta.placeholder='Nota privada (visivel apenas para a equipe)...';
    }
}

function startPoll(){stopPoll();pollTimer=setInterval(function(){loadMessages(true);},30000);}
function stopPoll(){if(pollTimer){clearInterval(pollTimer);pollTimer=null;}}

/* ── Public API ──────────────────────────────────────────────────────────── */
window.lfChatwootModal={
    open:function(){
        buildModal();
        el('lf-chatwoot-modal').style.display='block';
        document.body.style.overflow='hidden';
        loadMessages();
        startPoll();
    },
    close:function(){
        el('lf-chatwoot-modal').style.display='none';
        document.body.style.overflow='';
        stopPoll();
    },
    refreshMessages:function(){loadMessages();},
    toggleMacros:function(){
        macrosOpen=!macrosOpen;
        var d=el('lf-cwt-macros-drawer');
        if(d)d.style.display=macrosOpen?'block':'none';
        if(macrosOpen)loadMacros();
    },
    runMacro:runMacro,
    applyCanned:applyCanned,
    send:send,
    setMode:setMode
};

document.addEventListener('keydown',function(e){
    var m=el('lf-chatwoot-modal');
    if(e.key==='Escape'&&m&&m.style.display!=='none')window.lfChatwootModal.close();
});
})();
</script>
@else
<script>
/* Lead sem conversa: modal mostra estado vazio */
window.lfChatwootModal={
    open:function(){
        var m=document.getElementById('lf-chatwoot-modal');
        if(!m)return;
        var isDark=document.documentElement.classList.contains('dark');
        var bg=isDark?'#111827':'#ffffff';
        var border=isDark?'#374151':'#e5e7eb';
        var text=isDark?'#f3f4f6':'#111827';
        var muted=isDark?'#9ca3af':'#6b7280';
        var subtle=isDark?'#6b7280':'#9ca3af';
        var bkdrop=isDark?'rgba(0,0,0,.65)':'rgba(0,0,0,.4)';
        m.innerHTML=[
            '<div onclick="window.lfChatwootModal.close()" style="position:absolute;inset:0;background:'+bkdrop+';"></div>',
            '<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;padding:1rem;pointer-events:none;">',
              '<div style="pointer-events:all;background:'+bg+';border:1px solid '+border+';border-radius:14px;width:100%;max-width:480px;overflow:hidden;">',
                '<div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-bottom:1px solid '+border+';">',
                  '<span style="font-size:.88rem;font-weight:700;color:'+text+';">&#x1F4AC; Conversa Chatwoot</span>',
                  '<button onclick="window.lfChatwootModal.close()" style="background:none;border:none;cursor:pointer;color:'+muted+';font-size:1rem;">&#x2715;</button>',
                '</div>',
                '<div style="padding:2.5rem 1.5rem;text-align:center;display:flex;flex-direction:column;align-items:center;gap:.75rem;">',
                  '<span style="font-size:2.5rem;">&#x1F4ED;</span>',
                  '<p style="margin:0;font-size:.9rem;color:'+muted+';">Este lead nao possui uma conversa Chatwoot vinculada.</p>',
                  '<p style="margin:0;font-size:.78rem;color:'+subtle+';">A conversa e criada automaticamente quando o lead entra em contato pelo WhatsApp.</p>',
                '</div>',
              '</div>',
            '</div>',
        ].join('');
        m.style.display='block';
        document.body.style.overflow='hidden';
    },
    close:function(){
        var m=document.getElementById('lf-chatwoot-modal');
        if(m)m.style.display='none';
        document.body.style.overflow='';
    }
};
document.addEventListener('keydown',function(e){
    var m=document.getElementById('lf-chatwoot-modal');
    if(e.key==='Escape'&&m&&m.style.display!=='none')window.lfChatwootModal.close();
});
</script>
@endif
