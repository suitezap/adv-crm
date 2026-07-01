<x-dynamic-component :component="request()->has('clean') ? 'admin::layouts.anonymous' : 'admin::layouts'">
    <x-slot:title>Agenda Jurídica</x-slot:title>

    <div class="flex flex-col gap-4 {{ request()->has('clean') ? 'p-2' : 'p-4' }}">
        @if(request()->has('clean'))
            <!-- Clean Header -->
            <div class="flex items-center justify-between px-2 pt-2">
                <h1 class="text-xl font-bold text-gray-800 dark:text-white">📅 Agenda Jurídica</h1>
                <button id="lf-btn-new-event"
                    class="primary-button text-sm px-3 py-1.5"
                    onclick="window.lfOpenCreateModal()">
                    + Novo Compromisso
                </button>
            </div>
        @else
            <!-- Default Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800 dark:text-white">📅 Agenda Jurídica</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Audiências, Reuniões, Tarefas e Prazos — tudo em um só lugar.</p>
                </div>
                <button id="lf-btn-new-event"
                    class="primary-button"
                    onclick="window.lfOpenCreateModal()">
                    + Novo Compromisso
                </button>
            </div>
        @endif

        <!-- Legenda -->
        <div class="flex flex-wrap gap-4 items-center text-sm {{ request()->has('clean') ? 'px-2' : '' }}">
            <span class="flex items-center gap-1.5 font-medium text-gray-700">
                <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#5b21b6;"></span>
                🏛️ Audiências
            </span>
            <span class="flex items-center gap-1.5 font-medium text-gray-700">
                <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#3b82f6;"></span>
                Atividades CRM
            </span>
            <span class="flex items-center gap-1.5 font-medium text-gray-700">
                <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#ef4444;"></span>
                Prazos Pendentes
            </span>
            <span class="flex items-center gap-1.5 font-medium text-gray-700">
                <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#22c55e;"></span>
                Prazos Concluídos
            </span>
            <span class="flex items-center gap-1.5 font-medium text-gray-500">
                <span style="display:inline-block;width:11px;height:11px;border-radius:50%;background:#9ca3af;"></span>
                Concluídos
            </span>
        </div>

        <!-- Iframe container isolado do Vue.js -->
        <div id="lf-agenda-wrapper" style="background:#fff; border-radius:8px; border:1px solid #e5e7eb; overflow:hidden; min-height:600px; {{ request()->has('clean') ? 'height:calc(100vh - 120px)' : '' }}">
            <iframe id="lf-agenda-iframe" style="width:100%; height: {{ request()->has('clean') ? '100%' : '780px' }}; border:none;" srcdoc=""></iframe>
        </div>
    </div>

    <!-- =====================================================================
         MODAL DE CRIAÇÃO / VISUALIZAÇÃO DE COMPROMISSO
         Renderizado no DOM pai (fora do iframe) para acesso total ao layout
         do Krayin CRM sem conflitos com Vue.js (Regra 6.1 da arquitetura).
    ====================================================================== -->
    <div id="lf-event-modal" class="hidden relative z-[10002]">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-50 transition-opacity"></div>

        <!-- Container principal -->
        <div class="fixed inset-0 z-[10003] transform overflow-y-auto transition">
            <div class="flex min-h-full items-center justify-center max-md:p-4">
                <div class="box-shadow z-[999] w-full max-w-[525px] overflow-visible rounded-lg bg-white dark:bg-gray-900 sm:absolute">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between gap-2.5 border-b px-4 py-3 dark:border-gray-800">
                        <h2 id="lf-modal-title" class="text-base font-bold text-gray-800 dark:text-white">🏛️ Nova Atividade</h2>
                        <span onclick="lfCloseModal()" class="icon-cross-large cursor-pointer text-3xl hover:rounded-md hover:bg-gray-100 dark:hover:bg-gray-950"></span>
                    </div>

                    <!-- ==============================================
                         Conteúdo do modal — modo VIEW
                         ============================================== -->
                    <div id="lf-modal-view-container" class="hidden">
                        <div class="border-b px-4 py-2.5 dark:border-gray-800 space-y-2">
                            <div>
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Título</p>
                                <p id="lf-view-title" class="text-gray-800 dark:text-gray-300 font-medium"></p>
                            </div>
                            <div id="lf-view-comment-wrap" class="hidden">
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Comentário</p>
                                <p id="lf-view-comment" class="text-gray-700 dark:text-gray-400 text-sm whitespace-pre-wrap"></p>
                            </div>
                            <div id="lf-view-participants-wrap" class="hidden">
                                <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Participantes</p>
                                <ul id="lf-view-participants" class="flex flex-wrap gap-1 mt-1"></ul>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Tipo</p>
                                    <p id="lf-view-tipo" class="text-gray-700 dark:text-gray-400 text-sm font-medium"></p>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Vencimento</p>
                                    <p id="lf-view-time" class="text-gray-700 dark:text-gray-400 text-sm"></p>
                                </div>
                                <div id="lf-view-status-wrap" class="hidden">
                                    <p class="text-xs font-semibold text-gray-400 uppercase mb-0.5">Status</p>
                                    <p id="lf-view-status" class="text-gray-700 dark:text-gray-400 text-sm"></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 px-4 py-2.5">
                            <button onclick="lfCloseModal()" class="secondary-button">
                                Fechar
                            </button>
                            <a id="lf-btn-edit" href="#" target="_parent" class="primary-button hidden">
                                Editar
                            </a>
                        </div>
                    </div>

                    <!-- ==============================================
                         Conteúdo do modal — modo CREATE
                         ============================================== -->
                    <div id="lf-modal-create-container" class="block">
                        <div class="border-b px-4 py-2.5 dark:border-gray-800 space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Título *</label>
                                <input id="lf-inp-titulo" type="text" maxlength="255"
                                    class="w-full border border-gray-300 dark:border-gray-800 dark:bg-gray-900 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-300 outline-none dark:text-gray-300"
                                    placeholder="Ex: Audiência de conciliação">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Tipo *</label>
                                <select id="lf-field-tipo" class="w-full border border-gray-300 dark:border-gray-800 dark:bg-gray-900 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-300 outline-none dark:text-gray-300">
                                    <option value="call">📋 Tarefa</option>
                                    <option value="meeting" selected>🤝 Reunião</option>
                                    <option value="lunch">⚖️ Processo</option>
                                    <option value="email">✉️ E-mail</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Comentário</label>
                                <textarea id="lf-inp-desc" rows="2" maxlength="2000"
                                    class="w-full border border-gray-300 dark:border-gray-800 dark:bg-gray-900 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-300 outline-none resize-none dark:text-gray-300"
                                    placeholder="Observações, local, informações adicionais..."></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Vencimento *</label>
                                    <input id="lf-inp-inicio" type="datetime-local"
                                        class="w-full border border-gray-300 dark:border-gray-800 dark:bg-gray-900 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-300 outline-none dark:text-gray-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Fim</label>
                                    <input id="lf-inp-fim" type="datetime-local"
                                        class="w-full border border-gray-300 dark:border-gray-800 dark:bg-gray-900 rounded-lg px-2 py-1.5 text-xs focus:ring-2 focus:ring-blue-300 outline-none dark:text-gray-300">
                                </div>
                            </div>
                            <!-- Participants -->
                            <div class="w-full">
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-0.5">Participantes</label>
                                <div class="relative w-full">
                                    <div class="relative rounded border border-gray-300 dark:border-gray-800 dark:bg-gray-900 px-2 py-1 hover:border-gray-400 focus-within:ring-2 focus-within:ring-blue-300 transition-shadow transition-colors min-h-[38px] flex items-center w-full" role="button" id="lf-participants-container">
                                        <ul class="flex flex-wrap items-center gap-1 w-full" id="lf-participants-list">
                                            <li class="flex-grow min-w-[200px]">
                                                <input type="text" id="lf-participants-search" class="w-full px-1 py-0.5 text-sm dark:bg-gray-900 dark:text-gray-300 focus:outline-none bg-transparent" placeholder="Digite para pesquisar participantes">
                                            </li>
                                        </ul>
                                        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-2xl icon-down-arrow text-gray-400 pointer-events-none"></span>
                                    </div>
                                    <!-- dropdown -->
                                    <div id="lf-participants-dropdown" class="absolute z-10 w-full rounded bg-white shadow-[0px_10px_20px_0px_#0000001F] dark:bg-gray-900 hidden left-0 top-full mt-1 border border-gray-200 dark:border-gray-800">
                                        <ul class="flex flex-col gap-1 p-2 max-h-60 overflow-y-auto">
                                            <li class="flex flex-col gap-2">
                                                <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 px-2 border-b dark:border-gray-800 pb-1">Usuários</h3>
                                                <ul id="lf-participants-users-list" class="flex flex-col"></ul>
                                            </li>
                                            <li class="flex flex-col gap-2 mt-2">
                                                <h3 class="text-sm font-bold text-gray-600 dark:text-gray-300 px-2 border-b dark:border-gray-800 pb-1">Pessoas</h3>
                                                <ul id="lf-participants-persons-list" class="flex flex-col"></ul>
                                            </li>
                                            <li id="lf-participants-no-results" class="hidden rounded-sm px-5 py-2 text-sm text-gray-800 dark:text-white">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum resultado encontrado...</p>
                                            </li>
                                            <li id="lf-participants-loading" class="hidden rounded-sm px-5 py-2 text-sm text-gray-800 dark:text-white">
                                                <p class="text-sm text-gray-500 dark:text-gray-400">Buscando...</p>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <!-- Sem Checkbox de Status (Sempre Pendente ao criar) -->
                            <p id="lf-modal-error" class="hidden text-red-600 text-xs mt-1"></p>
                        </div>
                        <div class="flex justify-end gap-2 px-4 py-2.5">
                            <button onclick="lfCloseModal()" class="secondary-button">
                                Cancelar
                            </button>
                            <button id="lf-btn-save" onclick="lfSaveEvent()" class="primary-button">
                                Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var eventsUrl  = @json(route('admin.lawfirm.agenda.events'));
            var updateBase = @json(route('admin.lawfirm.agenda.update', 'REPLACE_ID'));
            var storeUrl   = @json(route('admin.lawfirm.agenda.store'));

            // ------------------------------------------------------------------
            // Helpers de modal
            // ------------------------------------------------------------------
            window.lfCloseModal = function () {
                document.getElementById('lf-event-modal').classList.add('hidden');
                document.getElementById('lf-modal-error').classList.add('hidden');
                document.getElementById('lf-modal-error').textContent = '';
                // Reset ao modo CREATE
                document.getElementById('lf-modal-view-container').classList.add('hidden');
                document.getElementById('lf-modal-create-container').classList.remove('hidden');
                document.getElementById('lf-modal-title').textContent = '🏛️ Nova Atividade';
            };

            // Participants variables and logic
            var lfParticipants = { users: [], persons: [] };
            var lfParticipantSearchTimer = null;
            var usersSearchUrl = "{{ route('admin.settings.users.search') }}";
            var personsSearchUrl = "{{ route('admin.contacts.persons.search') }}";

            // Render array of users/persons to DOM
            window.lfRenderParticipants = function () {
                var listEl = document.getElementById('lf-participants-list');
                var inputLi = listEl.lastElementChild; 
                listEl.innerHTML = '';
                
                ['users', 'persons'].forEach(function(type) {
                    lfParticipants[type].forEach(function(p) {
                        var li = document.createElement('li');
                        li.className = 'flex items-center gap-1 rounded-md bg-slate-100 pl-2 dark:bg-gray-950 dark:text-gray-300 text-sm py-0.5 border border-gray-200 dark:border-gray-800';
                        li.innerHTML = `
                            ${p.name}
                            <span class="icon-cross-large cursor-pointer p-0.5 text-base text-gray-400 hover:text-red-500" data-id="${p.id}" data-type="${type}"></span>
                        `;
                        li.querySelector('.icon-cross-large').addEventListener('click', function(e) {
                            var toRemoveId = parseInt(this.getAttribute('data-id'));
                            var toRemoveType = this.getAttribute('data-type');
                            lfParticipants[toRemoveType] = lfParticipants[toRemoveType].filter(function(x) { return x.id !== toRemoveId; });
                            window.lfRenderParticipants();
                        });
                        listEl.appendChild(li);
                    });
                });
                listEl.appendChild(inputLi);
            };

            // Input behavior (Debounced Search via Event Delegation)
            document.addEventListener('input', function(e) {
                if (e.target && e.target.id === 'lf-participants-search') {
                    var val = e.target.value.trim();
                    var dropdown = document.getElementById('lf-participants-dropdown');
                    var usersList = document.getElementById('lf-participants-users-list');
                    var personsList = document.getElementById('lf-participants-persons-list');
                    var noResults = document.getElementById('lf-participants-no-results');
                    var loading = document.getElementById('lf-participants-loading');

                    if (val.length < 2) {
                        if(dropdown) dropdown.classList.add('hidden');
                        return;
                    }

                    if(dropdown) dropdown.classList.remove('hidden');
                    if(usersList) usersList.innerHTML = '';
                    if(personsList) personsList.innerHTML = '';
                    if(noResults) noResults.classList.add('hidden');
                    if(loading) loading.classList.remove('hidden');

                    clearTimeout(lfParticipantSearchTimer);
                    lfParticipantSearchTimer = setTimeout(function() {
                        var opts = {
                            method: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        };
                        var qs = '?search=' + encodeURIComponent('name:' + val) + '&searchFields=' + encodeURIComponent('name:like');
                        console.log('Buscando participantes:', usersSearchUrl + qs);
                        Promise.all([
                            fetch(usersSearchUrl + qs, opts).then(function(r) { return r.json(); }),
                            fetch(personsSearchUrl + qs, opts).then(function(r) { return r.json(); })
                        ]).then(function(responses) {
                            if(loading) loading.classList.add('hidden');
                            var users = (responses[0].data || []).slice(0, 5);
                            var persons = (responses[1].data || []).slice(0, 5);

                            // filter out already added
                            users = users.filter(function(u) { return !lfParticipants.users.some(function(x){ return x.id === u.id; }); });
                            persons = persons.filter(function(p) { return !lfParticipants.persons.some(function(x){ return x.id === p.id; }); });

                            if (users.length === 0 && persons.length === 0) {
                                if(noResults) noResults.classList.remove('hidden');
                            } else {
                                users.forEach(function(u) {
                                    var li = document.createElement('li');
                                    li.className = 'cursor-pointer rounded-sm px-3 py-2 text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950';
                                    li.textContent = u.name;
                                    li.addEventListener('click', function() {
                                        lfParticipants.users.push(u);
                                        window.lfRenderParticipants();
                                        document.getElementById('lf-participants-search').value = '';
                                        if(dropdown) dropdown.classList.add('hidden');
                                    });
                                    if(usersList) usersList.appendChild(li);
                                });
                                persons.forEach(function(p) {
                                    var li = document.createElement('li');
                                    li.className = 'cursor-pointer rounded-sm px-3 py-2 text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-950';
                                    li.textContent = p.name;
                                    li.addEventListener('click', function() {
                                        lfParticipants.persons.push(p);
                                        window.lfRenderParticipants();
                                        document.getElementById('lf-participants-search').value = '';
                                        if(dropdown) dropdown.classList.add('hidden');
                                    });
                                    if(personsList) personsList.appendChild(li);
                                });
                            }
                        }).catch(function(e) {
                            console.error("Erro ao buscar participantes:", e);
                            if(loading) loading.classList.add('hidden');
                            if(noResults) noResults.classList.remove('hidden');
                        });
                    }, 500);
                }
            });

            // Closes dropdown on click outside
            document.addEventListener('click', function(e) {
                var container = document.getElementById('lf-participants-container');
                if (container && !container.contains(e.target)) {
                    var dropdown = document.getElementById('lf-participants-dropdown');
                    if(dropdown) dropdown.classList.add('hidden');
                }
            });

            // Fecha o modal ao clicar fora
            document.getElementById('lf-event-modal').addEventListener('click', function(e) {
                if (e.target === this) window.lfCloseModal();
            });

            // ------------------------------------------------------------------
            // Salvar novo compromisso
            // ------------------------------------------------------------------
            window.lfSaveEvent = function () {
                var titulo = document.getElementById('lf-inp-titulo').value.trim();
                var tipo   = document.getElementById('lf-field-tipo').value;
                var desc = document.getElementById('lf-inp-desc').value;
                var ini = document.getElementById('lf-inp-inicio').value;
                var fim = document.getElementById('lf-inp-fim').value;
                var errEl  = document.getElementById('lf-modal-error');

                if (!titulo) {
                    errEl.textContent = 'O título é obrigatório.';
                    errEl.classList.remove('hidden');
                    return;
                }
                if (!ini) {
                    errEl.textContent = 'A data e hora de início são obrigatórias.';
                    errEl.classList.remove('hidden');
                    return;
                }

                var btn = document.getElementById('lf-btn-save');
                btn.disabled = true;
                btn.textContent = 'Salvando…';

                var freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                fetch(storeUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': freshToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        titulo:      titulo,
                        tipo:        tipo,
                        descricao:   desc,
                        data_inicio: ini,
                        data_fim:    fim || null,
                        is_done:     false,
                        participants: {
                            users: lfParticipants.users.map(function(u){ return u.id; }),
                            persons: lfParticipants.persons.map(function(p){ return p.id; })
                        },
                        _token:      freshToken
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        window.lfCloseModal();
                        // Instrui o iframe a refazer o fetch dos eventos
                        var iframe = document.getElementById('lf-agenda-iframe');
                        if (iframe && iframe.contentWindow) {
                            iframe.contentWindow.postMessage({ type: 'lf-agenda-refetch' }, '*');
                        }
                    } else {
                        errEl.textContent = data.message || 'Erro ao salvar. Tente novamente.';
                        errEl.classList.remove('hidden');
                    }
                })
                .catch(function() {
                    errEl.textContent = 'Erro de rede. Verifique sua conexão.';
                    errEl.classList.remove('hidden');
                })
                .finally(function() {
                    btn.disabled = false;
                    btn.textContent = 'Salvar';
                });
            };

            // ------------------------------------------------------------------
            // Exibir modal de VISUALIZAÇÃO quando o iframe avisa clique num evento
            // ------------------------------------------------------------------
            function lfShowViewModal(props) {
                document.getElementById('lf-modal-create-container').classList.add('hidden');
                document.getElementById('lf-modal-view-container').classList.remove('hidden');
                document.getElementById('lf-modal-title').textContent = props.title || 'Compromisso';
                document.getElementById('lf-view-title').textContent = props.title || '';

                var timeEl = document.getElementById('lf-view-time');
                if (props.start) {
                    var s = new Date(props.start);
                    var e = props.end ? new Date(props.end) : null;
                    var fmt = {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'};
                    timeEl.textContent = s.toLocaleString('pt-BR', fmt) + (e ? ' → ' + e.toLocaleString('pt-BR', {hour:'2-digit',minute:'2-digit'}) : '');
                } else {
                    timeEl.textContent = '—';
                }

                var cmtEl = document.getElementById('lf-view-comment');
                var cmtWrap = document.getElementById('lf-view-comment-wrap');
                if (props.comment && props.comment.trim()) {
                    cmtEl.textContent = props.comment.trim();
                    cmtWrap.classList.remove('hidden');
                } else {
                    cmtWrap.classList.add('hidden');
                }

                var partWrap = document.getElementById('lf-view-participants-wrap');
                var partList = document.getElementById('lf-view-participants');
                if (props.participants && props.participants.length > 0) {
                    partList.innerHTML = '';
                    props.participants.forEach(function(p) {
                        var li = document.createElement('li');
                        li.className = 'text-xs rounded-md bg-slate-100 px-2 py-0.5 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 font-medium';
                        li.textContent = (p.type === 'user' ? '👤 ' : '👥 ') + p.name;
                        partList.appendChild(li);
                    });
                    partWrap.classList.remove('hidden');
                } else {
                    partWrap.classList.add('hidden');
                }

                var statusWrap = document.getElementById('lf-view-status-wrap');
                var statusEl = document.getElementById('lf-view-status');
                if (props.is_done !== undefined && props.is_done !== null) {
                    statusWrap.classList.remove('hidden');
                    statusEl.textContent = props.is_done ? '✅ Concluído' : '⏳ Pendente';
                } else {
                    statusWrap.classList.add('hidden');
                }

                var tipoEl = document.getElementById('lf-view-tipo');
                if (tipoEl) {
                    if (props.type === 'prazo') {
                        tipoEl.textContent = '⚖️ Prazo';
                    } else if (props.isAudiencia) {
                        tipoEl.textContent = '🏛️ Audiência';
                    } else {
                        var tipoMap = {
                            'call': '📋 Tarefa',
                            'meeting': '🤝 Reunião',
                            'lunch': '⚖️ Processo',
                            'email': '✉️ E-mail'
                        };
                        tipoEl.textContent = tipoMap[props.activityType] || '📋 Atividade';
                    }
                }

                var btnEdit = document.getElementById('lf-btn-edit');
                if (props.type === 'activity' && props.id) {
                    // Mapeia para a edição global de atividades Krayin
                    var url = "{{ route('admin.activities.edit', 'PID') }}".replace('PID', props.id);
                    btnEdit.href = url;
                    btnEdit.classList.remove('hidden');
                } else if (props.type === 'prazo' && props.processo_id) {
                    // Prazos são editados dentro do Processo
                    var url = "{{ route('admin.processos.edit', 'PID') }}".replace('PID', props.processo_id) + '?tab=prazos';
                    btnEdit.href = url;
                    btnEdit.classList.remove('hidden');
                } else {
                    btnEdit.classList.add('hidden');
                }

                document.getElementById('lf-event-modal').classList.remove('hidden');
            }

            // ------------------------------------------------------------------
            // Abrir modal de criação com data pré-preenchida (via dateClick)
            // ------------------------------------------------------------------
            window.lfOpenCreateModal = function (dateStr) {
                window.lfCloseModal(); // garante modo create limpo
                
                var dt = '';
                var dtEnd = '';
                if (dateStr) {
                    // dateStr vem como '2026-05-07' (dia inteiro) ou '2026-05-07T09:00:00' (hora)
                    dt = dateStr.length === 10 ? dateStr + 'T09:00' : dateStr.substring(0, 16);
                    dtEnd = dateStr.length === 10 ? dateStr + 'T10:00' : '';
                    if (!dtEnd && dt) {
                        // +1h
                        var d = new Date(dt); d.setHours(d.getHours() + 1);
                        dtEnd = d.toISOString().substring(0, 16);
                    }
                } else {
                    // Use now
                    var now = new Date();
                    var tzoffset = now.getTimezoneOffset() * 60000;
                    dt = (new Date(Date.now() - tzoffset)).toISOString().substring(0, 16);
                    var endD = new Date(now.getTime() + 3600000); // +1h
                    dtEnd = (new Date(endD.getTime() - tzoffset)).toISOString().substring(0, 16);
                }

                document.getElementById('lf-inp-inicio').value = dt;
                document.getElementById('lf-inp-fim').value = dtEnd;
                document.getElementById('lf-inp-titulo').value = '';
                document.getElementById('lf-inp-desc').value = '';
                document.getElementById('lf-field-tipo').value = 'meeting';
                
                lfParticipants = { users: [], persons: [] };
                window.lfRenderParticipants();
                document.getElementById('lf-participants-search').value = '';
                document.getElementById('lf-participants-dropdown').classList.add('hidden');
                
                document.getElementById('lf-event-modal').classList.remove('hidden');
                document.getElementById('lf-inp-titulo').focus();
            };

            // ------------------------------------------------------------------
            // postMessage bridge: drag-drop + dateClick + eventClick + refetch
            // ------------------------------------------------------------------
            window.__lfAgendaUpdate = function (tipo, realId, newStart, newEnd, onSuccess, onError) {
                var url = updateBase.replace('REPLACE_ID', realId);
                var freshToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': freshToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        tipo: tipo, new_start: newStart, new_end: newEnd, _token: freshToken
                    })
                })
                .then(function (r) {
                    if (r.ok) { if (onSuccess) onSuccess(); }
                    else       { if (onError)   onError();  }
                })
                .catch(function () { if (onError) onError(); });
            };

            window.addEventListener('message', function (e) {
                var iframe = document.getElementById('lf-agenda-iframe');
                if (!iframe || e.source !== iframe.contentWindow) return;
                var d = e.data;
                if (!d) return;

                if (d.type === 'lf-agenda-update') {
                    window.__lfAgendaUpdate(
                        d.eventType, d.id, d.newStart, d.newEnd,
                        function () { iframe.contentWindow.postMessage({ type: 'lf-agenda-ack', reqId: d.reqId, ok: true  }, '*'); },
                        function () { iframe.contentWindow.postMessage({ type: 'lf-agenda-ack', reqId: d.reqId, ok: false }, '*'); }
                    );
                }

                if (d.type === 'lf-agenda-dateclick') {
                    window.lfOpenCreateModal(d.date);
                }

                if (d.type === 'lf-agenda-eventclick') {
                    lfShowViewModal(d.props);
                }
            });

            // ------------------------------------------------------------------
            // HTML do iframe (DOM isolado do Vue.js)
            // ------------------------------------------------------------------
            var brandColor = getComputedStyle(document.documentElement).getPropertyValue('--brand-color').trim() || '#0E90D9';

            var iframeHtml = '<!DOCTYPE html>' +
                '<html lang="pt-br"><head>' +
                '<meta charset="UTF-8">' +
                '<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"><\/script>' +
                '<style>' +
                ':root { --brand-color: ' + brandColor + '; }' +
                'body{margin:0;padding:16px;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}' +
                '.fc-toolbar-title{font-size:1.25rem!important;font-weight:600!important;}' +
                '.fc-button{font-size:.8rem!important;padding:.35rem .75rem!important; transition: all 0.2s ease;}' +
                '.fc-button-primary{background-color:var(--brand-color)!important; border-color:var(--brand-color)!important; opacity:0.85;}' +
                '.fc-button-primary:hover{opacity:1; filter:brightness(1.1);}' +
                '.fc-button-primary:not(:disabled).fc-button-active, .fc-button-primary:not(:disabled):active{background-color:var(--brand-color)!important; border-color:var(--brand-color)!important; opacity:1; filter:brightness(0.85); box-shadow:inset 0 3px 5px rgba(0,0,0,0.125)!important;}' +
                '.fc-daygrid-day-number{font-size:.85rem;padding:4px 8px;}' +
                '.fc-event{cursor:pointer;border-radius:4px!important;padding:2px 5px!important;font-size:.78rem!important;}' +
                '.fc-list-empty{padding:2rem;font-size:.9rem;color:#6b7280;}' +
                '.fc-daygrid-day:hover{background:#f9fafb;cursor:pointer;}' +
                '<\/style>' +
                '<\/head><body>' +
                '<div id="lf-cal"><\/div>' +
                '<script>' +
                '(function(){' +
                '  var pending={};' +
                '  var calRef=null;' +

                '  window.addEventListener("message",function(e){' +
                '    if(!e.data)return;' +
                '    if(e.data.type==="lf-agenda-ack"){' +
                '      var cb=pending[e.data.reqId];' +
                '      if(!cb)return;' +
                '      delete pending[e.data.reqId];' +
                '      if(e.data.ok){cb.resolve();}else{cb.reject();}' +
                '    }' +
                '    if(e.data.type==="lf-agenda-refetch"&&calRef){' +
                '      calRef.refetchEvents();' +
                '    }' +
                '  });' +

                '  function postUpdate(tipo,id,newStart,newEnd){' +
                '    return new Promise(function(resolve,reject){' +
                '      var reqId=Date.now()+"_"+Math.random();' +
                '      pending[reqId]={resolve:resolve,reject:reject};' +
                '      window.parent.postMessage({type:"lf-agenda-update",reqId:reqId,eventType:tipo,id:id,newStart:newStart,newEnd:newEnd},"*");' +
                '    });' +
                '  }' +

                '  document.addEventListener("DOMContentLoaded",function(){' +
                '    calRef=new FullCalendar.Calendar(document.getElementById("lf-cal"),{' +
                '      initialView:"dayGridMonth",' +
                '      locale:"pt-br",' +
                '      height:"auto",' +
                '      headerToolbar:{left:"prev,next today",center:"title",right:"dayGridMonth,timeGridWeek,timeGridDay,listWeek"},' +
                '      buttonText:{today:"Hoje",month:"Mês",week:"Semana",day:"Dia",list:"Lista"},' +
                '      noEventsText:"Nenhum evento para exibir.",' +
                '      editable:true,' +
                '      droppable:true,' +
                '      navLinks:true,' +
                '      dayMaxEvents:true,' +
                '      events:{url:"' + eventsUrl + '",method:"GET"},' +

                '      dateClick:function(info){' +
                '        window.parent.postMessage({type:"lf-agenda-dateclick",date:info.dateStr},"*");' +
                '      },' +

                '      eventClick:function(info){' +
                '        var p=info.event.extendedProps;' +
                '        window.parent.postMessage({type:"lf-agenda-eventclick",props:{' +
                '          title:info.event.title,' +
                '          start:info.event.startStr,' +
                '          end:info.event.endStr,' +
                '          comment:p.comment||"",' +
                '          status:p.status||"",' +
                '          processo:p.processo||"",' +
                '          processo_id:p.processo_id||null,' +
                '          is_done:p.isDone,' +
                '          type:p.type,' +
                '          id:p.id,' +
                '          isAudiencia:p.isAudiencia||false,' +
                '          activityType:p.activityType||"",' +
                '          participants:p.participants||[]' +
                '        }},"*");' +
                '      },' +

                '      eventDrop:function(info){' +
                '        var p=info.event.extendedProps;' +
                '        postUpdate(p.type,p.id,info.event.startStr,info.event.endStr||info.event.startStr)' +
                '          .catch(function(){alert("Erro ao mover evento. A data foi revertida.");info.revert();});' +
                '      },' +

                '      eventResize:function(info){' +
                '        var p=info.event.extendedProps;' +
                '        postUpdate(p.type,p.id,info.event.startStr,info.event.endStr||info.event.startStr)' +
                '          .catch(function(){info.revert();});' +
                '      },' +

                '      eventDidMount:function(info){' +
                '        var x=info.event.extendedProps;' +
                '        var tip=info.event.title;' +
                '        if(x.processo)tip+="\\nProcesso: "+x.processo;' +
                '        if(x.status)tip+="\\nStatus: "+x.status;' +
                '        if(x.comment&&x.comment.trim())tip+="\\n"+x.comment.substring(0,80);' +
                '        info.el.setAttribute("title",tip);' +
                '      }' +
                '    });' +
                '    calRef.render();' +
                '    window.addEventListener("resize",function(){calRef.updateSize();});' +
                '  });' +
                '})();' +
                '<\/script><\/body><\/html>';

            setTimeout(function () {
                var iframe = document.getElementById('lf-agenda-iframe');
                if (iframe) iframe.srcdoc = iframeHtml;
            }, 300);
        })();
    </script>
</x-dynamic-component>
