{{-- ═══════════════════════════════════════════════════════════════════════════
Whatsapp Messenger — Premium UI (ARCHITECTURE.md §6.1 §6.2 §6.6)
Pattern: Portal Dialog + Vanilla JS + Tailwind (no Vue injection)
═══════════════════════════════════════════════════════════════════════════ --}}
<x-admin::layouts>
    <x-slot:title>Mensageiro WhatsApp</x-slot>

        {{-- ─── WhatsApp-style background pattern ─────────────────────────────── --}}
        <style>
            #wt-bg {
                background-color: #efeae2;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80'%3E%3Ccircle cx='40' cy='40' r='1.5' fill='%23c8bfb6' fill-opacity='0.4'/%3E%3C/svg%3E");
            }

            .wt-bubble-in {
                border-radius: 0 12px 12px 12px;
            }

            .wt-bubble-out {
                border-radius: 12px 0 12px 12px;
            }

            #wt-ticket-list::-webkit-scrollbar,
            #wt-msg-area::-webkit-scrollbar {
                width: 5px;
            }

            #wt-ticket-list::-webkit-scrollbar-thumb,
            #wt-msg-area::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 10px;
            }

            .wt-ticket-row.active {
                background: #f0fdf4 !important;
                border-left: 3px solid #25D366;
            }

            .wt-ticket-row {
                border-left: 3px solid transparent;
            }

            #wt-search:focus {
                outline: none;
            }
        </style>

        {{-- ═══ Root Wrapper ═══════════════════════════════════════════════════════ --}}
        <div class="flex border border-gray-200 rounded-xl overflow-hidden shadow-lg bg-white"
            style="height: calc(100vh - 100px); font-family: 'Inter', system-ui, sans-serif;">

            {{-- ════════════════════════════════════════════════════════════════════
            LEFT PANEL ─ Sidebar Inbox
            ════════════════════════════════════════════════════════════════════ --}}
            <div class="flex flex-col bg-white border-r border-gray-200" style="width: 340px; flex-shrink: 0;">

                {{-- ── Top Bar ──────────────────────────────────────────────────── --}}
                <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-9 h-9 rounded-full bg-green-500 flex items-center justify-center text-white font-bold text-sm">
                            WA</div>
                        <span class="font-bold text-gray-800 text-sm">Atendimento</span>
                    </div>
                    <button onclick="lfM.promptNewChat()"
                        class="w-9 h-9 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-600 cursor-pointer border-0 bg-transparent transition-colors"
                        title="Nova Conversa">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </div>

                {{-- ── Search ───────────────────────────────────────────────────── --}}
                <div class="px-3 py-2 border-b border-gray-100 bg-white">
                    <div class="flex items-center gap-2 bg-gray-100 rounded-full px-3 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none"
                            viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input id="wt-search" type="text" placeholder="Pesquisar…"
                            class="flex-1 bg-transparent text-sm text-gray-700 placeholder-gray-400 border-0"
                            oninput="lfM.searchTickets(this.value)">
                    </div>
                </div>

                {{-- ── Status Tabs ──────────────────────────────────────────────── --}}
                <div class="flex border-b border-gray-200 bg-white">
                    <button id="wt-tab-all"
                        class="wt-tab flex-1 py-2 text-xs font-semibold text-green-600 border-b-2 border-green-500 cursor-pointer bg-transparent"
                        onclick="lfM.loadTickets(null, 'all')">Todos</button>
                    <button id="wt-tab-pending"
                        class="wt-tab flex-1 py-2 text-xs font-semibold text-gray-500 border-b-2 border-transparent cursor-pointer bg-transparent"
                        onclick="lfM.loadTickets('pending', 'pending')">⏳ Aguardando</button>
                    <button id="wt-tab-open"
                        class="wt-tab flex-1 py-2 text-xs font-semibold text-gray-500 border-b-2 border-transparent cursor-pointer bg-transparent"
                        onclick="lfM.loadTickets('open', 'open')">✅ Abertos</button>
                    <button id="wt-tab-closed"
                        class="wt-tab flex-1 py-2 text-xs font-semibold text-gray-500 border-b-2 border-transparent cursor-pointer bg-transparent"
                        onclick="lfM.loadTickets('closed', 'closed')">🔒</button>
                </div>

                {{-- ── Ticket List ──────────────────────────────────────────────── --}}
                <div id="wt-ticket-list" class="flex-1 overflow-y-auto bg-white">
                    <div class="flex items-center justify-center h-20 text-gray-400 text-sm gap-2">
                        <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Carregando conversas...
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════════════════
            RIGHT PANEL ─ Chat Window
            ════════════════════════════════════════════════════════════════════ --}}
            <div class="flex flex-col flex-1" style="min-width: 0;">

                {{-- ── Empty State ──────────────────────────────────────────────── --}}
                <div id="wt-empty" class="flex flex-col flex-1 items-center justify-center bg-gray-50 gap-4">
                    <div
                        class="w-20 h-20 rounded-full bg-green-50 border-2 border-green-200 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-green-400" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-700 font-semibold text-base">Selecione um atendimento</p>
                        <p class="text-gray-400 text-sm mt-1">Escolha uma conversa na lista ou inicie uma nova</p>
                    </div>
                    <button onclick="lfM.promptNewChat()"
                        class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white font-semibold text-sm rounded-full cursor-pointer border-0 transition-colors shadow-sm">
                        + Nova Conversa
                    </button>
                </div>

                {{-- ── Chat Header ──────────────────────────────────────────────── --}}
                <div id="wt-header"
                    class="hidden items-center justify-between px-5 py-3 bg-gray-50 border-b border-gray-200"
                    style="min-height: 60px;">
                    <div class="flex items-center gap-3 min-w-0">
                        <div id="wt-hdr-avatar"
                            class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">
                            ?</div>
                        <div class="min-w-0">
                            <p id="wt-hdr-name" class="font-bold text-gray-800 text-sm truncate">—</p>
                            <p id="wt-hdr-phone" class="text-xs text-gray-500 truncate">—</p>
                        </div>
                    </div>
                    <div id="wt-hdr-actions" class="flex items-center gap-2 flex-shrink-0"></div>
                </div>

                {{-- ── Messages Area ────────────────────────────────────────────── --}}
                <div id="wt-bg" class="hidden flex-col flex-1 overflow-y-auto px-4 py-4 gap-2" id-ref="wt-msg-area">
                    <div id="wt-msg-area" class="flex flex-col gap-2 flex-1"></div>
                </div>

                {{-- ── Input Bar ────────────────────────────────────────────────── --}}
                <div id="wt-input-bar" class="hidden items-center gap-2 px-4 py-3 bg-gray-50 border-t border-gray-200">
                    {{-- Hidden file input --}}
                    <input type="file" id="wt-file" class="hidden" onchange="lfM.handleFile(this)"
                        accept="image/*,audio/*,video/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">

                    {{-- Attach Icon --}}
                    <button title="Anexar arquivo" onclick="elById('wt-file').click()"
                        class="w-9 h-9 rounded-full hover:bg-gray-200 flex items-center justify-center text-gray-500 cursor-pointer border-0 bg-transparent flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                            stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" />
                        </svg>
                    </button>
                    {{-- Text input --}}
                    <div
                        class="flex-1 bg-white border border-gray-200 rounded-full px-4 py-2 flex items-center shadow-sm">
                        <textarea id="wt-txt" rows="1" placeholder="Digite uma mensagem…"
                            class="flex-1 bg-transparent text-sm text-gray-800 resize-none outline-none border-0 max-h-24"></textarea>
                    </div>
                    {{-- Send Button --}}
                    <button id="wt-send-btn" onclick="lfM.sendMessage()"
                        class="w-10 h-10 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center flex-shrink-0 cursor-pointer border-0 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                            class="w-5 h-5 ml-0.5">
                            <path
                                d="M3.478 2.405a.75.75 0 0 0-.926.94l2.432 7.905H13.5a.75.75 0 0 1 0 1.5H4.984l-2.432 7.905a.75.75 0 0 0 .926.94 60.519 60.519 0 0 0 18.445-8.986.75.75 0 0 0 0-1.218A60.517 60.517 0 0 0 3.478 2.405Z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', function () {

                    // ── Routes ────────────────────────────────────────────────────────────
                    const RT = {
                        tickets: "{{ route('admin.lawfirm.whatsapp.messenger.tickets') }}",
                        messages: "{{ route('admin.lawfirm.whatsapp.messenger.messages', 'REPLACE_ID') }}",
                        accept: "{{ route('admin.lawfirm.whatsapp.messenger.accept', 'REPLACE_ID') }}",
                        close: "{{ route('admin.lawfirm.whatsapp.messenger.close', 'REPLACE_ID') }}",
                        send: "{{ route('admin.lawfirm.whatsapp.messenger.send', 'REPLACE_ID') }}",
                        sendMedia: "{{ route('admin.lawfirm.whatsapp.messenger.send_media', 'REPLACE_ID') }}",
                        upload: "{{ route('admin.lawfirm.whatsapp.messenger.upload') }}",
                        start: "{{ route('admin.lawfirm.whatsapp.messenger.start') }}",
                    };
                    const CSRF = "{{ csrf_token() }}";

                    // ── State ─────────────────────────────────────────────────────────────
                    let activeTicketId = null;
                    let activeTicketStatus = null;
                    let currentFilter = null;
                    let allTickets = [];    // full snapshot for local search
                    let pollTimer = null;

                    // ── API ───────────────────────────────────────────────────────────────
                    const api = (url, opts = {}) => fetch(url, {
                        headers: {
                            'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json', ...(opts.headers || {})
                        },
                        ...opts
                    }).then(r => r.ok ? r.json() : Promise.reject(r));

                    // ── Helpers ───────────────────────────────────────────────────────────
                    const elById = id => document.getElementById(id);
                    const fmt = iso => iso ? new Date(iso).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '';
                    const initial = str => (str || '?')[0].toUpperCase();

                    function setTab(key) {
                        document.querySelectorAll('.wt-tab').forEach(b => {
                            b.classList.remove('text-green-600', 'border-green-500');
                            b.classList.add('text-gray-500', 'border-transparent');
                        });
                        const el = elById('wt-tab-' + key);
                        if (el) {
                            el.classList.add('text-green-600', 'border-green-500');
                            el.classList.remove('text-gray-500', 'border-transparent');
                        }
                    }

                    function showChat(show) {
                        elById('wt-empty').classList.toggle('hidden', show);
                        elById('wt-empty').classList.toggle('flex', !show);

                        ['wt-header', 'wt-input-bar'].forEach(id => {
                            elById(id).classList.toggle('hidden', !show);
                            elById(id).classList.toggle('flex', show);
                        });

                        const bg = elById('wt-bg');
                        bg.classList.toggle('hidden', !show);
                        bg.classList.toggle('flex', show);
                    }

                    // ── Tickets ───────────────────────────────────────────────────────────
                    function loadTickets(status, tabKey, silent = false) {
                        currentFilter = status;
                        if (tabKey) setTab(tabKey);

                        const url = status ? `${RT.tickets}?status=${status}` : RT.tickets;
                        if (!silent) {
                            elById('wt-ticket-list').innerHTML =
                                `<div class="flex items-center justify-center h-20 text-gray-400 text-sm gap-2">
                                <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>Carregando…</div>`;
                        }
                        api(url).then(d => {
                            allTickets = d.data ?? [];
                            renderTickets(allTickets);
                        }).catch(() => {
                            if (!silent) elById('wt-ticket-list').innerHTML =
                                `<div class="text-red-400 text-xs text-center p-4">Erro ao carregar conversas.<br>Verifique se as migrations foram executadas.</div>`;
                        });
                    }

                    function renderTickets(list) {
                        if (!list.length) {
                            elById('wt-ticket-list').innerHTML =
                                `<div class="flex flex-col items-center justify-center h-32 gap-2 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 opacity-40" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                                </svg>
                                <span class="text-sm">Nenhuma conversa</span>
                            </div>`;
                            return;
                        }

                        const statusBadge = {
                            pending: `<span class="text-xs px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 font-medium border border-amber-200">Aguardando</span>`,
                            open: `<span class="text-xs px-1.5 py-0.5 rounded-full bg-green-50 text-green-700 font-medium border border-green-200">Aberto</span>`,
                            closed: `<span class="text-xs px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500 font-medium border border-gray-200">Fechado</span>`,
                        };

                        elById('wt-ticket-list').innerHTML = list.map(t => {
                            const name = t.contact?.name || t.contact?.phone || '—';
                            const phone = t.contact?.phone || '';
                            const preview = t.latest_message?.body?.text ?? (t.latest_message ? '📎 Mídia' : 'Nova conversa');
                            const ts = fmt(t.updated_at);
                            const badge = statusBadge[t.status] || '';
                            const isMe = activeTicketId === t.id;
                            return `
                        <div class="wt-ticket-row flex items-center gap-3 px-4 py-3 cursor-pointer hover:bg-gray-50 border-b border-gray-100 transition-colors ${isMe ? 'active' : ''}"
                             data-id="${t.id}" data-name="${name}" data-phone="${phone}" data-status="${t.status}"
                             onclick="lfM.openTicket(${t.id}, this)">
                            <div class="w-11 h-11 rounded-full bg-gradient-to-br from-green-400 to-green-600 text-white flex items-center justify-center font-bold text-base flex-shrink-0 shadow-sm">
                                ${initial(name)}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-1">
                                    <p class="font-semibold text-gray-800 text-sm truncate">${name}</p>
                                    <span class="text-gray-400 text-xs flex-shrink-0">${ts}</span>
                                </div>
                                <div class="flex items-center justify-between gap-1 mt-0.5">
                                    <p class="text-xs text-gray-400 truncate">${preview}</p>
                                    ${badge}
                                </div>
                            </div>
                        </div>`;
                        }).join('');
                    }

                    // ── Search ────────────────────────────────────────────────────────────
                    function searchTickets(q) {
                        if (!q) { renderTickets(allTickets); return; }
                        const lq = q.toLowerCase();
                        renderTickets(allTickets.filter(t =>
                            (t.contact?.name || '').toLowerCase().includes(lq) ||
                            (t.contact?.phone || '').toLowerCase().includes(lq)
                        ));
                    }

                    // ── Open Ticket ───────────────────────────────────────────────────────
                    function openTicket(id, row) {
                        activeTicketId = id;
                        activeTicketStatus = row?.dataset?.status || 'pending';

                        // Highlight row
                        document.querySelectorAll('.wt-ticket-row').forEach(r => r.classList.remove('active'));
                        row?.classList.add('active');

                        const name = row?.dataset?.name || '—';
                        const phone = row?.dataset?.phone || '';

                        elById('wt-hdr-avatar').textContent = initial(name);
                        elById('wt-hdr-name').textContent = name;
                        elById('wt-hdr-phone').textContent = phone;
                        renderHdrActions(id, activeTicketStatus);

                        showChat(true);

                        elById('wt-msg-area').innerHTML = `
                        <div class="flex items-center justify-center h-full text-gray-400 text-sm gap-2">
                            <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>Carregando mensagens…</div>`;

                        api(RT.messages.replace('REPLACE_ID', id))
                            .then(d => renderMessages(d.data ?? []))
                            .catch(() => {
                                elById('wt-msg-area').innerHTML = `<div class="text-red-400 text-xs text-center p-4">Erro ao carregar mensagens.</div>`;
                            });

                        // Show/hide send bar based on status
                        elById('wt-input-bar').classList.toggle('hidden', activeTicketStatus === 'closed');
                        elById('wt-input-bar').classList.toggle('flex', activeTicketStatus !== 'closed');

                        startPolling();
                    }

                    function renderMessages(msgs, silent = false) {
                        const area = elById('wt-msg-area');
                        const atBottom = area.scrollTop + area.clientHeight >= area.scrollHeight - 40;

                        if (!msgs.length) {
                            area.innerHTML = `<div class="flex items-center justify-center flex-1 text-gray-400 text-sm">
                            Sem mensagens ainda. Envie a primeira!
                        </div>`;
                            return;
                        }

                        area.innerHTML = msgs.map(m => {
                            const isMe = !!m.from_me;
                            const body = m.body || {};
                            const ts = fmt(m.created_at);
                            const ackIcon = isMe ? { 0: '⏳', 1: '✓', 2: '✓✓', 3: '✓✓' }[m.ack ?? 0] || '' : '';
                            const ackCls = isMe && m.ack >= 3 ? 'text-blue-400' : 'text-gray-400';

                            let content = '';
                            if (body.mediaUrl) {
                                const mime = (body.mime || '');
                                if (mime.includes('image')) {
                                    content = `<img src="${body.mediaUrl}" class="rounded-lg max-w-full max-h-48 object-cover" alt="Imagem">`;
                                } else if (mime.includes('audio')) {
                                    content = `<audio controls class="max-w-full"><source src="${body.mediaUrl}"></audio>`;
                                } else {
                                    content = `<a href="${body.mediaUrl}" target="_blank" class="underline text-blue-600 text-xs">📎 Baixar arquivo</a>`;
                                }
                                if (body.text) content += `<p class="text-sm mt-1">${body.text}</p>`;
                            } else {
                                content = `<p class="text-sm leading-snug whitespace-pre-wrap">${body.text ?? '(mensagem vazia)'}</p>`;
                            }

                            const align = isMe ? 'items-end' : 'items-start';
                            const bg = isMe ? 'bg-green-100 text-gray-800' : 'bg-white text-gray-800';
                            const rnd = isMe ? 'wt-bubble-out' : 'wt-bubble-in';

                            return `<div class="flex flex-col ${align} w-full">
                            <div class="${bg} ${rnd} max-w-xs lg:max-w-sm xl:max-w-md px-3 py-2 shadow-sm">
                                ${content}
                                <div class="flex items-center justify-end gap-1 mt-1">
                                    <span class="text-gray-400" style="font-size:10px;">${ts}</span>
                                    ${isMe ? `<span class="${ackCls}" style="font-size:10px;">${ackIcon}</span>` : ''}
                                </div>
                            </div>
                        </div>`;
                        }).join('');

                        if (!silent || atBottom) {
                            area.scrollTop = area.scrollHeight;
                        }
                    }

                    function renderHdrActions(id, status) {
                        let html = '';
                        if (status === 'pending') {
                            html += `<button onclick="lfM.accept(${id})"
                            class="px-4 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs font-bold rounded-full border-0 cursor-pointer transition-colors shadow-sm">
                            ✅ Aceitar
                        </button>`;
                        }
                        if (status !== 'closed') {
                            html += `<button onclick="lfM.close(${id})"
                            class="px-4 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-bold rounded-full border border-red-200 cursor-pointer transition-colors">
                            🔒 Encerrar
                        </button>`;
                        }
                        if (status === 'closed') {
                            html += `<span class="text-xs text-gray-400 italic">Conversa encerrada</span>`;
                        }
                        elById('wt-hdr-actions').innerHTML = html;
                    }

                    // ── Actions ───────────────────────────────────────────────────────────
                    function accept(id) {
                        api(RT.accept.replace('REPLACE_ID', id), { method: 'POST', body: '{}' }).then(() => {
                            activeTicketStatus = 'open';
                            renderHdrActions(id, 'open');
                            elById('wt-input-bar').classList.remove('hidden');
                            elById('wt-input-bar').classList.add('flex');
                            loadTickets(currentFilter, null, true);
                        });
                    }

                    function close(id) {
                        if (!confirm('Encerrar esta conversa?')) return;
                        api(RT.close.replace('REPLACE_ID', id), { method: 'POST', body: '{}' }).then(() => {
                            showChat(false);
                            activeTicketId = null;
                            stopPolling();
                            loadTickets(currentFilter, null, true);
                        });
                    }

                    function sendMessage() {
                        const ta = elById('wt-txt');
                        const text = ta.value.trim();
                        if (!text || !activeTicketId) return;
                        ta.value = '';
                        ta.style.height = 'auto';

                        // Optimistic bubble
                        const area = elById('wt-msg-area');
                        const ts = new Date().toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                        area.insertAdjacentHTML('beforeend', `
                        <div class="flex flex-col items-end w-full">
                            <div class="bg-green-100 text-gray-800 wt-bubble-out max-w-xs lg:max-w-sm px-3 py-2 shadow-sm">
                                <p class="text-sm leading-snug whitespace-pre-wrap">${text}</p>
                                <div class="flex items-center justify-end gap-1 mt-1">
                                    <span class="text-gray-400" style="font-size:10px;">${ts}</span>
                                    <span class="text-gray-400" style="font-size:10px;">⏳</span>
                                </div>
                            </div>
                        </div>`);
                        area.scrollTop = area.scrollHeight;

                        api(RT.send.replace('REPLACE_ID', activeTicketId), {
                            method: 'POST', body: JSON.stringify({ text })
                        });
                    }

                    function handleFile(input) {
                        const file = input.files[0];
                        if (!file || !activeTicketId) return;

                        const formData = new FormData();
                        file.name && formData.append('file', file);

                        // Optimistic feedback
                        const area = elById('wt-msg-area');
                        const tempId = 'msg-temp-' + Date.now();
                        area.insertAdjacentHTML('beforeend', `
                        <div id="${tempId}" class="flex flex-col items-end w-full">
                            <div class="bg-green-100 text-gray-800 wt-bubble-out max-w-xs px-3 py-2 shadow-sm animate-pulse">
                                <span class="text-xs italic text-gray-500">Enviando ${file.name}...</span>
                            </div>
                        </div>`);
                        area.scrollTop = area.scrollHeight;

                        // 1. Upload
                        fetch(RT.upload, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                            body: formData
                        })
                            .then(r => r.json())
                            .then(res => {
                                // 2. Send Media
                                return api(RT.sendMedia.replace('REPLACE_ID', activeTicketId), {
                                    method: 'POST',
                                    body: JSON.stringify({ url: res.url, type: res.type })
                                });
                            })
                            .then(() => {
                                elById(tempId)?.remove();
                                input.value = ''; // Reset
                                // Refresh
                                api(RT.messages.replace('REPLACE_ID', activeTicketId))
                                    .then(d => renderMessages(d.data ?? []));
                            })
                            .catch(err => {
                                console.error(err);
                                const el = elById(tempId);
                                if (el) el.innerHTML = '<div class="text-xs text-red-500 bg-red-50 p-1 rounded">Erro ao enviar arquivo</div>';
                                setTimeout(() => el?.remove(), 3000);
                            });
                    }

                    function promptNewChat() {
                        const phone = prompt('Número do WhatsApp com DDD (somente números):\nEx: 11999998888');
                        if (!phone) return;
                        api(RT.start, { method: 'POST', body: JSON.stringify({ phone }) })
                            .then(r => {
                                if (!r.ok) { alert('Erro: ' + (r.message || 'Tente novamente.')); return; }
                                loadTickets(null, 'all');
                                setTimeout(() => {
                                    const row = document.querySelector(`#wt-ticket-list [data-id="${r.ticket.id}"]`);
                                    openTicket(r.ticket.id, row);
                                }, 600);
                            });
                    }

                    // ── Polling ───────────────────────────────────────────────────────────
                    function startPolling() {
                        stopPolling();
                        pollTimer = setInterval(() => {
                            loadTickets(currentFilter, null, true);
                            if (activeTicketId) {
                                api(RT.messages.replace('REPLACE_ID', activeTicketId))
                                    .then(d => renderMessages(d.data ?? [], true));
                            }
                        }, 10000);
                    }
                    function stopPolling() { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }

                    // ── Textarea auto-height ──────────────────────────────────────────────
                    elById('wt-txt').addEventListener('input', function () {
                        this.style.height = 'auto';
                        this.style.height = Math.min(this.scrollHeight, 96) + 'px';
                    });
                    elById('wt-txt').addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); lfM.sendMessage(); }
                    });

                    // ── Public API ────────────────────────────────────────────────────────
                    window.lfM = { loadTickets, openTicket, accept, close, sendMessage, promptNewChat, searchTickets, handleFile };

                    // Boot
                    loadTickets(null, 'all');
                });
            </script>
        @endpush
</x-admin::layouts>