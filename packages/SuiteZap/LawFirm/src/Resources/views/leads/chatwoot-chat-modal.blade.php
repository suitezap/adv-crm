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
    $urlStageUpdate = route('admin.lawfirm.chatwoot.lead.stage.update',     ['lead' => $leadId]);
    $pipelineStages = $lead->lead_pipeline_id ? \Webkul\Lead\Models\Stage::where('lead_pipeline_id', $lead->lead_pipeline_id)->orderBy('sort_order')->get() : collect([]);
    $currentStageId = $lead->lead_pipeline_stage_id;
    // Dados para contexto da Agenda e botão WhatsApp
    $leadTitle  = $lead->title ?? '';
    $leadPerson = optional($lead->person)->name ?? '';
    $leadPhone  = '';
    $rawContacts = optional($lead->person)->contact_numbers ?? [];
    if (is_array($rawContacts) && count($rawContacts) > 0) {
        $firstContact = reset($rawContacts);
        if (is_array($firstContact) && !empty($firstContact['value'])) {
            $leadPhone = (string) $firstContact['value'];
        } elseif (is_string($firstContact) || is_numeric($firstContact)) {
            $leadPhone = (string) $firstContact;
        }
    }
    if (empty($leadPhone)) {
        $leadPhone = (string) (optional($lead->person)->phone ?? $lead->phone ?? '');
    }
@endphp

{{-- MODAL ROOT — always hidden at start --}}
<div id="lf-chatwoot-modal" role="dialog" aria-modal="true" aria-labelledby="lf-cwt-title"
     style="display:none;position:fixed;inset:0;z-index:10002;">
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
       type=1 outgoing = funcionário/agente (Nuno) → RIGHT (verde estilo WhatsApp)
       type=0 incoming = cliente/lead (Maria) → LEFT (cinza neutro)
    */
    get agentBg()     { return isDark()?'#14532d':'#dcfce7'; },   /* verde suave WhatsApp */
    get agentText()   { return isDark()?'#bbf7d0':'#14532d'; },
    get agentBorder() { return isDark()?'#166534':'#bbf7d0'; },
    get leadBg()      { return isDark()?'#1f2937':'#f3f4f6'; },   /* cinza neutro */
    get leadText()    { return isDark()?'#f3f4f6':'#111827'; },
    get leadBorder()  { return isDark()?'#374151':'#e5e7eb'; },
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
var URL_STAGE  =@json($urlStageUpdate);
var CONV_URL = @json($chatwootConvUrl);
var pipelineStages = @json($pipelineStages);
var currentStageId = @json($currentStageId);

var currentMode='reply';
var macrosLoaded=false;
var macrosOpen=false;
var pollTimer=null;

// Audio & Attachment state
var selectedFiles = [];
var audioBlob = null;
var mediaRecorder = null;
var audioChunks = [];
var isRecording = false;
var currentMessages = []; // Add local state for loaded messages

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
    var isFormData = opts.body instanceof FormData;
    var headers = Object.assign({
        'Accept':'application/json',
        'X-Requested-With':'XMLHttpRequest',
        'X-XSRF-TOKEN':getXsrfToken()
    }, opts.headers||{});
    
    if(!isFormData && !headers['Content-Type']) {
        headers['Content-Type']='application/json';
    }

    return fetch(url,Object.assign({},opts,{
        credentials:'same-origin',
        headers:headers
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
    // padding-top: 68px clears the Krayin sticky header (~60px, z-10001).
    // overflow-y:auto allows scrolling if modal taller than viewport.
    wrap.style.cssText='position:absolute;inset:0;display:flex;align-items:flex-start;justify-content:center;padding:68px 1rem 1rem;pointer-events:none;overflow-y:auto;';

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
    var calendarUrl = '{{ route("admin.lawfirm.agenda.index") }}'
        + '?clean=true'
        + '&conversa_id=' + encodeURIComponent(@json((string) $convId))
        + '&nome=' + encodeURIComponent(@json((string) $leadPerson))
        + '&titulo=' + encodeURIComponent(@json((string) $leadTitle))
        + '&telefone=' + encodeURIComponent(@json((string) $leadPhone))
        + '&lead_id=' + encodeURIComponent(@json((string) $leadId));
    var calendarBtn = '<a href="'+calendarUrl+'" target="_blank" onclick="window.open(this.href,\'AgendaJuridica\',\'width=1200,height=800,left=100,top=100\');return false;" title="Abrir Agenda" style="display:inline-flex;align-items:center;gap:.3rem;font-size:.71rem;color:'+T.accent+';text-decoration:none;padding:.25rem .5rem;border:1px solid '+T.borderMuted+';border-radius:5px;cursor:pointer;">&#x1F4C5; Agenda</a>';


    var extLink = CONV_URL
        ? '<a href="'+esc(CONV_URL)+'" target="_blank" title="'+esc(CONV_URL)+'" style="display:inline-flex;align-items:center;gap:.3rem;font-size:.71rem;color:'+T.accent+';text-decoration:none;padding:.25rem .5rem;border:1px solid '+T.borderMuted+';border-radius:5px;">&#x2197; Abrir no Chatwoot</a>'
        : '';

    return [
        /* Header */
        '<div id="lf-cwt-header" style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-bottom:1px solid '+T.border+';background:'+T.bgSubtle+';flex-shrink:0;">',
          '<div style="display:flex;align-items:center;gap:.6rem;">',
            '<div>',
              '<h3 id="lf-cwt-title" style="margin:0;font-size:.88rem;font-weight:700;color:'+T.text+';display:flex;align-items:baseline;gap:.25rem;flex-wrap:wrap;">',
                'Chat do Lead',
                '<span id="lf-cwt-conv-label" style="font-size:.75rem;font-weight:400;color:'+T.textMuted+';"> - Conversa #'+convId+'</span>',
                '<span id="lf-cwt-status" style="font-size:.7rem;font-weight:400;color:'+T.textSubtle+';"></span>',
              '</h3>',
            '</div>',
          '</div>',
          '<div style="display:flex;align-items:center;gap:.5rem;">',
            calendarBtn,
            extLink,
            '<button id="lf-cwt-close" style="background:none;border:none;cursor:pointer;color:'+T.textMuted+';font-size:1rem;padding:.25rem .4rem;border-radius:5px;line-height:1;">&#x2715;</button>',
          '</div>',
        '</div>',
        /* Toolbar & Stages */
        '<div id="lf-cwt-toolbar" style="display:flex;align-items:center;gap:.5rem;padding:.5rem 1rem;border-bottom:1px solid '+T.border+';background:'+T.bgSubtle+';flex-shrink:0;flex-wrap:wrap;">',
          
          /* Toolbar Actions */
          '<div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">',
            '<button id="lf-cwt-btn-macros" style="'+btnStyle()+'">&#x26A1; Macros</button>',
            '<button id="lf-cwt-btn-refresh" style="'+btnStyle()+'">&#x21BA; Atualizar</button>',
          '</div>',

          /* Funnel Stages */
          '<div id="lf-cwt-stages-bar" style="display:flex;align-items:center;gap:.4rem;overflow-x:auto;scrollbar-width:none;margin-left:.5rem;border-left:1px solid '+T.borderMuted+';padding-left:.75rem;">',
            pipelineStages.map(function(s){
                var isActive = (s.id == currentStageId);
                var bg = isActive ? '#16a34a' : T.bg; // Krayin Green when active
                var color = isActive ? '#fff' : T.text;
                var border = isActive ? '#16a34a' : T.border;
                // Same base styles as btnStyle() but customized for active state
                return '<button onclick="window.lfChatwootModal.changeStage('+s.id+', this)" style="display:inline-flex;align-items:center;gap:.3rem;padding:.3rem .6rem;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s;border:1px solid '+border+';background:'+bg+';color:'+color+';box-shadow:0 1px 2px rgba(0,0,0,0.05);">'+esc(s.name)+'</button>';
            }).join(''),
          '</div>',

          '<span style="flex:1;"></span>',
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
          /* Tabs row */
          '<div style="display:flex;gap:.4rem;margin-bottom:.4rem;align-items:center;">',
            '<button id="lf-cwt-tab-reply" style="'+tabReplyActiveStyle()+'">&#x21A9; Responder</button>',
            '<button id="lf-cwt-tab-note"  style="'+tabInactiveStyle()+'">&#x1F512; Nota Privada</button>',

            /* Import button */
            '<div style="position:relative;display:inline-block;">',
              '<button id="lf-cwt-btn-import" title="Importar histórico de mensagens" style="'+btnStyle()+';padding:.22rem .5rem;font-size:.72rem;" onclick="window.lfChatwootModal.toggleImport(event)">&#x21BA; Importar</button>',
              /* Import panel */
              '<div id="lf-cwt-import-panel" style="display:none;position:absolute;left:0;bottom:calc(100% + 4px);z-index:200;background:'+T.bg+';border:1px solid '+T.border+';border-radius:8px;padding:.6rem .75rem;box-shadow:0 -8px 24px rgba(0,0,0,.18);min-width:220px;">',
                '<p style="margin:0 0 .4rem;font-size:.72rem;font-weight:700;color:'+T.textMuted+';text-transform:uppercase;letter-spacing:.04em;">Importar Histórico</p>',
                '<div style="display:flex;flex-direction:column;gap:.35rem;">',
                  '<label style="font-size:.75rem;color:'+T.text+';">',
                    '<input type="radio" name="lf-im-limit" value="50" checked style="margin-right:.3rem;">Últimas 50</label>',
                  '<label style="font-size:.75rem;color:'+T.text+';">',
                    '<input type="radio" name="lf-im-limit" value="100" style="margin-right:.3rem;">Últimas 100</label>',
                  '<label style="font-size:.75rem;color:'+T.text+';">',
                    '<input type="radio" name="lf-im-limit" value="all" style="margin-right:.3rem;">Todas as mensagens</label>',
                  '<label style="font-size:.75rem;color:'+T.text+';">',
                    '<input type="radio" name="lf-im-limit" value="date" style="margin-right:.3rem;">A partir de uma data</label>',
                  '<div id="lf-im-date-wrap" style="display:none;flex-direction:column;gap:.25rem;padding-left:.9rem;">',
                    '<input type="date" id="lf-im-date" style="font-size:.75rem;padding:.2rem .4rem;border:1px solid '+T.inputBorder+';border-radius:4px;background:'+T.inputBg+';color:'+T.inputText+';">',
                  '</div>',
                '</div>',
                '<button onclick="window.lfChatwootModal.doImport()" style="margin-top:.5rem;width:100%;'+btnStyle()+';background:'+T.accent+';color:#fff;border-color:'+T.accent+';">&#x21BA; Carregar</button>',
              '</div>',
            '</div>',

            '<span style="flex:1;"></span>',
            '<div id="lf-cwt-attachment-preview" style="display:none;align-items:center;gap:.3rem;padding:.2rem .4rem;background:'+T.bg+';border:1px dashed '+T.borderMuted+';border-radius:4px;font-size:.7rem;color:'+T.text+';max-width:200px;">',
               '<span id="lf-cwt-attachment-name" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>',
               '<button id="lf-cwt-cancel-attachment" title="Remover" style="background:none;border:none;color:#ef4444;cursor:pointer;font-size:.8rem;padding:0 .2rem;">&#x2715;</button>',
            '</div>',
          '</div>',
          /* Composer row */
          '<div style="display:flex;align-items:flex-end;gap:.5rem;">',
            /* Attach + Audio stacked above textarea */
            '<div style="display:flex;flex-direction:column;align-items:center;gap:.25rem;">',
              '<button id="lf-cwt-btn-attach" title="Anexar Arquivo" style="background:none;border:none;color:'+T.textMuted+';cursor:pointer;font-size:1.3rem;padding:.2rem .1rem;transition:color .2s;">&#x1F4CE;</button>',
              '<button id="lf-cwt-btn-audio"  title="Gravar Áudio"   style="background:none;border:none;color:'+T.textMuted+';cursor:pointer;font-size:1.3rem;padding:.2rem .1rem;transition:color .2s;">&#x1F3A4;</button>',
            '</div>',
            '<input type="file" id="lf-cwt-file-input" style="display:none" multiple>',
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
   Alinhamento padrão de chat (WhatsApp / Chatwoot):
     Funcionário / Atendente (type=1, outgoing) → DIREITA (quem está atendendo / Nuno)
     Cliente / Lead (type=0, incoming) → ESQUERDA (quem enviou / Maria)
     Nota privada → DIREITA, fundo amarelado
*/
function renderMessages(msgs){
    var box=el('lf-cwt-messages');
    if(!box)return;
    if(!msgs||!msgs.length){
        box.innerHTML='<div style="text-align:center;color:'+T.textSubtle+';font-size:.8rem;padding:2rem;">Nenhuma mensagem nesta conversa.</div>';
        return;
    }

    /* Date separator helper */
    function fmtDate(ts){
        if(!ts)return'';
        var d=new Date(typeof ts==='number'?ts*1000:ts);
        return d.toLocaleDateString('pt-BR',{weekday:'short',day:'2-digit',month:'short',year:'numeric'});
    }
    function fmtFull(ts){
        if(!ts)return'';
        var d=new Date(typeof ts==='number'?ts*1000:ts);
        return d.toLocaleString('pt-BR');
    }

    var lastDate='';
    var html=msgs.map(function(m){
        var type=m.message_type; // 0=incoming/lead, 1=outgoing/agent, 2=activity
        var isPriv=m.private===true;
        var content=m.content??'';
        var sender=m.sender&&m.sender.name?m.sender.name:(type===1?'Atendente':'Cliente');
        var isBot=type===1&&(sender.toLowerCase().indexOf('bot')!==-1||sender.toLowerCase().indexOf('assistente')!==-1||sender.toLowerCase().indexOf('ia')!==-1);
        var senderIcon=isBot?'&#x1F916; ':'&#x1F464; ';
        var time=fmt(m.created_at);
        var fullTs=fmtFull(m.created_at);
        var dateStr=fmtDate(m.created_at);

        /* Handle Attachments */
        var attachmentsHtml = '';
        if(m.attachments && m.attachments.length > 0) {
            attachmentsHtml = '<div style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">' +
                m.attachments.map(function(att) {
                    var url = att.data_url;
                    if(!url) return '';
                    if(att.file_type === 'image') {
                        return '<a href="'+url+'" target="_blank" style="display:block;"><img src="'+url+'" style="max-width:100%;max-height:220px;border-radius:6px;object-fit:contain;background:rgba(255,255,255,0.2);" alt="Imagem anexa" /></a>';
                    } else if(att.file_type === 'audio') {
                        var tBtn = '<button onclick="transcribeAudio('+m.id+', \''+url+'\', this)" style="align-self:flex-start;background:transparent;border:1px solid rgba(0,0,0,0.1);border-radius:4px;font-size:.7rem;cursor:pointer;padding:3px 6px;margin-top:2px;">📝 Transcrever</button>';
                        return '<div style="display:flex;flex-direction:column;gap:3px;"><audio controls style="max-width:100%;height:35px;"><source src="'+url+'" />Seu navegador não suporta áudio.</audio>'+tBtn+'</div>';
                    } else if(att.file_type === 'video') {
                        return '<video controls style="max-width:100%;max-height:220px;border-radius:6px;"><source src="'+url+'" /></video>';
                    } else {
                        // PDF ou outros documentos
                        return '<a href="'+url+'" target="_blank" style="display:inline-flex;align-items:center;gap:5px;padding:6px 10px;background:rgba(0,0,0,0.06);border-radius:6px;color:inherit;text-decoration:none;font-size:.75rem;font-weight:600;"><span style="font-size:1.1rem;">&#x1F4CE;</span> '+(att.fallback_title || 'Baixar arquivo anexo')+'</a>';
                    }
                }).join('') +
            '</div>';
        }

        var bubbleContent = esc(content) + attachmentsHtml;

        /* Date separator */
        var sep='';
        if(dateStr && dateStr!==lastDate){
            sep='<div style="text-align:center;margin:.5rem 0;"><span style="font-size:.67rem;color:'+T.textSubtle+';background:'+T.bgMuted+';border:1px solid '+T.border+';border-radius:20px;padding:.18rem .65rem;">'+esc(dateStr)+'</span></div>';
            lastDate=dateStr;
        }

        /* Activity / system event */
        if(type===2||type===3){
            return sep+'<div style="text-align:center;font-size:.7rem;padding:.2rem 0;color:'+T.textSubtle+';font-style:italic;" title="'+esc(fullTs)+'">'+esc(content)+'</div>';
        }

        /* Nota privada — DIREITA (feita pelo funcionário), fundo amarelo */
        if(isPriv){
            return sep+'<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-end;max-width:78%;align-items:flex-end;">'
                +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">&#x1F512; Nota privada'+(m.sender&&m.sender.name?' ('+esc(m.sender.name)+')':'')+'</span>'
                +'<div style="background:'+T.privBg+';color:'+T.privText+';border:1px dashed '+T.privBorder+';border-radius:12px;border-bottom-right-radius:3px;padding:.4rem .7rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;font-style:italic;" title="'+esc(fullTs)+'">'+bubbleContent+'</div>'
                +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
                +'</div>';
        }

        /* type=1 outgoing = Funcionário (Nuno) → DIREITA */
        if(type===1){
            return sep+'<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-end;max-width:78%;align-items:flex-end;">'
                +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">'+senderIcon+esc(sender)+'</span>'
                +'<div style="background:'+T.agentBg+';color:'+T.agentText+';border:1px solid '+T.agentBorder+';border-radius:12px;border-bottom-right-radius:3px;padding:.42rem .72rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;" title="'+esc(fullTs)+'">'+bubbleContent+'</div>'
                +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
                +'</div>';
        }

        /* type=0 incoming = Cliente (Maria) → ESQUERDA */
        return sep+'<div style="display:flex;flex-direction:column;gap:.12rem;align-self:flex-start;max-width:78%;align-items:flex-start;">'
            +'<span style="font-size:.67rem;font-weight:600;color:'+T.textMuted+';">'+senderIcon+esc(sender)+'</span>'
            +'<div style="background:'+T.leadBg+';color:'+T.leadText+';border:1px solid '+T.leadBorder+';border-radius:12px;border-bottom-left-radius:3px;padding:.42rem .72rem;font-size:.81rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;" title="'+esc(fullTs)+'">'+bubbleContent+'</div>'
            +'<span style="font-size:.65rem;color:'+T.textSubtle+';">'+time+'</span>'
            +'</div>';
    }).join('');

    box.innerHTML=html;
    /* Use requestAnimationFrame to scroll AFTER the DOM has painted,
       ensuring scrollHeight is calculated with final layout.
       Scroll to BOTTOM = most recent message (messages are oldest→newest). */
    requestAnimationFrame(function(){
        box.scrollTop=box.scrollHeight;
    });
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

    var btnAttach=el('lf-cwt-btn-attach');
    var fileInput=el('lf-cwt-file-input');
    if(btnAttach && fileInput){
        btnAttach.addEventListener('click',function(){fileInput.click();});
        fileInput.addEventListener('change',function(){handleFileSelect(this);});
    }

    var btnAudio=el('lf-cwt-btn-audio');
    if(btnAudio)btnAudio.addEventListener('click',function(){toggleAudioRecord();});
    
    var btnCancelAtt=el('lf-cwt-cancel-attachment');
    if(btnCancelAtt)btnCancelAtt.addEventListener('click',function(){clearAttachments();});

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
function loadMessages(silent, paramsStr){
    if(!silent)setStatus('Atualizando...', T.accent);
    // Append cache-bust timestamp so the browser never serves a stale response
    var url=URL_MSG+'?_='+Date.now() + (paramsStr ? '&'+paramsStr : '');
    return apiFetch(url).then(function(d){
        currentMessages = d.messages??[];
        renderMessages(currentMessages);
        /* Update subtitle with the conv ID the API actually returned */
        var lbl=el('lf-cwt-conv-label');
        if(lbl && d.conversation_id){
            lbl.innerHTML=' - Conversa #'+d.conversation_id+' &bull; '+(d.messages??[]).length+' mensagem(ns)';
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

function handleFileSelect(input) {
    if(!input.files || input.files.length===0) return;
    for(var i=0; i<input.files.length; i++){
        selectedFiles.push(input.files[i]);
    }
    updateAttachmentPreview();
}

function clearAttachments() {
    selectedFiles = [];
    audioBlob = null;
    audioChunks = [];
    var input = el('lf-cwt-file-input');
    if(input) input.value = '';
    updateAttachmentPreview();
}

function updateAttachmentPreview() {
    var preview = el('lf-cwt-attachment-preview');
    var nameEl = el('lf-cwt-attachment-name');
    if(!preview || !nameEl) return;
    
    if(audioBlob) {
        nameEl.innerHTML = '🎤 Áudio gravado';
        preview.style.display = 'flex';
    } else if(selectedFiles.length > 0) {
        nameEl.innerHTML = '📎 ' + selectedFiles.length + ' arquivo(s)';
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
        nameEl.innerHTML = '';
    }
}

function toggleAudioRecord() {
    // MediaDevices API requires HTTPS or localhost — HTTP blocks it entirely.
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Áudio requer HTTPS. Configure SSL no ambiente local.', '#ef4444');
        alert('Gravação de áudio não disponível: esta funcionalidade requer uma conexão segura (HTTPS).\n\nNo ambiente local, acesse via https://adv-crm.test ou configure o Nginx/Apache com SSL.');
        return;
    }

    if(isRecording) {
        if(mediaRecorder) mediaRecorder.stop();
    } else {
        navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
            audioChunks = [];
            audioBlob = null;
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = function(e) {
                if(e.data.size > 0) audioChunks.push(e.data);
            };
            mediaRecorder.onstop = function() {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                stream.getTracks().forEach(function(t){ t.stop(); });
                isRecording = false;
                var btn = el('lf-cwt-btn-audio');
                if(btn) btn.style.color = T.textMuted;
                setStatus('Áudio capturado — clique Enviar para postar');
                updateAttachmentPreview();
            };
            mediaRecorder.start();
            isRecording = true;
            var btn = el('lf-cwt-btn-audio');
            if(btn) btn.style.color = '#ef4444';
            setStatus('🔴 Gravando... clique novamente para parar', '#ef4444');
        }).catch(function(err) {
            if(err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                alert('Permissão de microfone negada. Clique no ícone de cadeado na barra de endereço e permita o acesso ao microfone.');
            } else {
                alert('Não foi possível acessar o microfone: ' + err.message);
            }
            console.error('[CWT Audio]', err);
        });
    }
}

function send(){
    var ta=el('lf-cwt-compose');
    var msg=(ta&&ta.value?ta.value:'').trim();
    
    if(!msg && selectedFiles.length === 0 && !audioBlob) return;
    
    var btn=el('lf-cwt-send-btn');
    if(btn){btn.disabled=true;btn.style.opacity='.5';}
    setStatus('Enviando...', T.accent);
    
    var hasMedia = (selectedFiles.length > 0 || audioBlob);
    var reqOpts = { method: 'POST' };
    
    if (hasMedia) {
        var fd = new FormData();
        fd.append('message', msg);
        fd.append('private', currentMode==='note' ? 'true' : 'false');
        for(var i=0; i<selectedFiles.length; i++){
            fd.append('attachments[]', selectedFiles[i]);
        }
        if(audioBlob) {
            fd.append('attachments[]', audioBlob, 'audio.webm');
        }
        reqOpts.body = fd;
    } else {
        reqOpts.body = JSON.stringify({message:msg,private:currentMode==='note'});
    }

    apiFetch(URL_SEND, reqOpts)
    .then(function(){
        if(ta)ta.value='';
        clearAttachments();
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

function startPoll(){stopPoll();pollTimer=setInterval(function(){loadMessages(true);},15000);}
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
    setMode:setMode,
    toggleImport:function(e){
        if(e)e.stopPropagation();
        var p=el('lf-cwt-import-panel');
        if(p)p.style.display=p.style.display==='none'?'block':'none';
    },
    doImport:function(){
        var p=document.querySelector('input[name="lf-im-limit"]:checked').value;
        var params = [];
        if (p === '50') params.push('limit=50');
        else if (p === '100') params.push('limit=100');
        else if (p === 'all') params.push('limit=all');
        else if (p === 'date') {
            var f=el('lf-im-date').value;
            if(!f){alert('Preencha a data inicial.');return;}
            params.push('since='+f);
        }
        
        var panel=el('lf-cwt-import-panel');
        if(panel)panel.style.display='none';
        
        loadMessages(false, params.join('&'));
    },
    changeStage:function(stageId, btn) {
        // Encontrar o stage para verificar o código
        var stage = pipelineStages.find(function(s) { return s.id == stageId; });
        if (stage && (stage.code === 'won' || stage.code === 'lost')) {
            alert('Para marcar o Lead como ' + stage.name + ', feche esta janela de chat e faça a alteração diretamente na tela do Lead, pois é necessário preencher ' + (stage.code === 'won' ? 'o valor' : 'o motivo') + '.');
            return;
        }

        var origHtml = btn.innerHTML;
        btn.innerHTML = '...';
        btn.disabled = true;
        
        apiFetch(URL_STAGE, {
            method: 'POST',
            body: JSON.stringify({ stage_id: stageId })
        })
        .then(function(data){
            if(data.error) {
                alert('Erro: ' + data.error);
                btn.innerHTML = origHtml;
                btn.disabled = false;
            } else {
                // Sucesso! Atualizar a variável atual e recarregar os botões na UI
                currentStageId = stageId;
                // Re-renderizar todo o modal interno para atualizar cores
                var root = el('lf-chatwoot-modal');
                if(root) {
                    var dlg = el('lf-cwt-dialog');
                    if(dlg) dlg.innerHTML = buildInnerHTML();
                    wireEvents();
                }
            }
        })
        .catch(function(err){
            console.error(err);
            alert('Erro ao atualizar etapa.');
            btn.innerHTML = origHtml;
            btn.disabled = false;
        });
    }
};

window.transcribeAudio = function(msgId, attachmentUrl, btn) {
    if(!confirm('Deseja enviar este áudio para transcrição? Isso consumirá créditos da OpenAI.')) return;
    
    var origText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '⏳ Transcrevendo...';
    
    var endpoint = '{{ route("admin.lawfirm.chatwoot.lead.transcribe", ["lead" => $lead->id, "messageId" => "__MSG__"]) }}'.replace('__MSG__', msgId);
    
    // apiFetch already returns parsed JSON — do NOT call .json() again
    apiFetch(endpoint, {
        method: 'POST',
        body: JSON.stringify({ attachment_url: attachmentUrl })
    })
    .then(function(data){
        if(data && data.error) {
            alert('Erro: ' + data.error);
            btn.innerHTML = '❌ Erro. ' + origText;
            btn.disabled = false;
        } else {
            btn.innerHTML = '✅ Transcrito';
            window.lfChatwootModal.refreshMessages();
        }
    })
    .catch(function(err){
        console.error('[CWT Transcribe]', err);
        var msg = (err && err.error) ? err.error : 'Erro de rede ao transcrever áudio.';
        alert(msg);
        btn.innerHTML = '❌ Falha.';
        btn.disabled = false;
    });
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
