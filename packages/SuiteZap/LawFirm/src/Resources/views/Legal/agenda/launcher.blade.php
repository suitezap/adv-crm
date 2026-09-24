<x-admin::layouts>
    <x-slot:title>
        Agenda Jurídica
    </x-slot:title>

    @push('styles')
        <style>
            :root {
                --agenda-100: #e0e7ff;
                --agenda-500: #6366f1;
                --agenda-600: #4f46e5;
                --agenda-700: #4338ca;
                --agenda-800: #3730a3;
            }

            .agenda-page {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                padding-bottom: 2rem;
            }

            /* ── Hero ── */
            .agenda-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                background: linear-gradient(135deg, #312e81 0%, #4338ca 40%, #4f46e5 70%, #6366f1 100%);
                padding: 2rem 2.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 1rem;
                box-shadow: 0 20px 60px rgba(79, 70, 229, 0.35), 0 4px 16px rgba(0,0,0,0.12);
            }

            .agenda-hero::before {
                content: '';
                position: absolute;
                top: -80px; right: -80px;
                width: 280px; height: 280px;
                border-radius: 50%;
                background: rgba(255,255,255,0.05);
                pointer-events: none;
            }

            .agenda-hero::after {
                content: '';
                position: absolute;
                bottom: -60px; left: -60px;
                width: 220px; height: 220px;
                border-radius: 50%;
                background: rgba(255,255,255,0.04);
                pointer-events: none;
            }

            .agenda-hero-title {
                color: #ffffff;
                font-size: 1.6rem;
                font-weight: 700;
                margin: 0;
                position: relative;
                z-index: 2;
            }

            .agenda-actions {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                justify-content: center;
                position: relative;
                z-index: 2;
            }

            .agenda-btn-main {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.75rem;
                background: #ffffff;
                color: var(--agenda-700);
                font-size: 0.95rem;
                font-weight: 700;
                border-radius: 0.75rem;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                text-decoration: none;
            }

            .agenda-btn-main:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                color: #312e81;
            }

            .agenda-btn-secondary {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.75rem 1.35rem;
                background: rgba(255,255,255,0.12);
                color: #ffffff;
                font-size: 0.875rem;
                font-weight: 600;
                border-radius: 0.75rem;
                border: 1px solid rgba(255,255,255,0.25);
                cursor: pointer;
                transition: all 0.2s ease;
                text-decoration: none;
                backdrop-filter: blur(4px);
            }

            .agenda-btn-secondary:hover {
                background: rgba(255,255,255,0.2);
                transform: translateY(-2px);
                color: #ffffff;
            }

            /* ── Indicador de janela aberta ── */
            .window-open-indicator {
                display: none;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                padding: 1.25rem 1.5rem;
                background: linear-gradient(135deg, #f0fdf4, #dcfce7);
                border: 1px solid #86efac;
                border-radius: 0.75rem;
                font-size: 0.9rem;
                color: #15803d;
                font-weight: 500;
            }

            .window-open-indicator.show { display: flex; }

            .window-indicator-dot {
                width: 10px; height: 10px;
                border-radius: 50%;
                background: #22c55e;
                box-shadow: 0 0 12px #22c55e;
                animation: statusPulse 2s infinite;
                flex-shrink: 0;
            }

            @keyframes statusPulse {
                0%,100% { opacity:1; transform:scale(1); }
                50%      { opacity:.5; transform:scale(1.3); }
            }

            /* ── Info Card Grid (igual SAC) ── */
            .agenda-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 1rem;
            }

            .agenda-info-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                padding: 1.25rem;
                display: flex;
                gap: 1rem;
                align-items: flex-start;
                transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            }

            .dark .agenda-info-card {
                background: #111827;
                border-color: #1f2937;
            }

            .agenda-info-card:hover {
                border-color: #a5b4fc;
                box-shadow: 0 4px 16px rgba(79, 70, 229, 0.08);
                transform: translateY(-2px);
            }

            .agenda-info-icon {
                flex-shrink: 0;
                width: 2.5rem; height: 2.5rem;
                border-radius: 0.625rem;
                background: var(--agenda-100);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }

            .dark .agenda-info-icon {
                background: rgba(99, 102, 241, 0.15);
            }

            .agenda-info-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #6b7280;
                margin-bottom: 0.2rem;
            }

            .dark .agenda-info-label { color: #9ca3af; }

            .agenda-info-value {
                font-size: 0.9rem;
                color: #111827;
                font-weight: 500;
                line-height: 1.4;
            }

            .dark .agenda-info-value { color: #f3f4f6; }
        </style>
    @endpush

    <div class="agenda-page">

        {{-- ── Hero com botões apenas ── --}}
        <div class="agenda-hero">
            <h1 class="agenda-hero-title">📅 Agenda Jurídica</h1>

            <div class="agenda-actions">
                <button type="button" class="agenda-btn-main" onclick="launchAgenda()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
                    </svg>
                    Abrir em Nova Janela
                </button>

                <a href="{{ route('admin.lawfirm.agenda.viewer') }}?clean=1" target="_blank" class="agenda-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Nova Aba
                </a>
            </div>
        </div>

        {{-- ── Indicador de janela aberta ── --}}
        <div class="window-open-indicator" id="cwWindowOpenIndicator">
            <span class="window-indicator-dot"></span>
            <span>Agenda aberta em janela separada — clique em <strong>Abrir em Nova Janela</strong> para trazer o foco de volta.</span>
        </div>

        {{-- ── Cards de Informação ── --}}
        <div class="agenda-info-grid">
            <div class="agenda-info-card">
                <div class="agenda-info-icon">📅</div>
                <div>
                    <div class="agenda-info-label">Prazos Processuais</div>
                    <div class="agenda-info-value">Visualize todos os prazos cadastrados nos processos em uma única visão</div>
                </div>
            </div>
            <div class="agenda-info-card">
                <div class="agenda-info-icon">🤝</div>
                <div>
                    <div class="agenda-info-label">Compromissos</div>
                    <div class="agenda-info-value">Reuniões, ligações e audiências agendadas com clientes e partes</div>
                </div>
            </div>
            <div class="agenda-info-card">
                <div class="agenda-info-icon">🖱️</div>
                <div>
                    <div class="agenda-info-label">Drag & Drop</div>
                    <div class="agenda-info-value">Reposicione compromissos arrastando-os diretamente no calendário</div>
                </div>
            </div>
            <div class="agenda-info-card">
                <div class="agenda-info-icon">🚀</div>
                <div>
                    <div class="agenda-info-label">Como Usar</div>
                    <div class="agenda-info-value">Clique em <strong>Abrir em Nova Janela</strong> para uma visualização ampliada e sem distrações</div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            var AGENDA_URL = "{{ route('admin.lawfirm.agenda.viewer') }}?clean=1";
            var agendaWindow = null;
            var agendaCheckInterval = null;

            function launchAgenda() {
                if (agendaWindow && !agendaWindow.closed) {
                    agendaWindow.focus();
                    return;
                }

                var width  = Math.min(1280, window.screen.availWidth  - 40);
                var height = Math.min(900,  window.screen.availHeight - 60);
                var left   = Math.round((window.screen.availWidth  - width)  / 2);
                var top    = Math.round((window.screen.availHeight - height) / 2);

                var features = [
                    'width='  + width,
                    'height=' + height,
                    'top='    + top,
                    'left='   + left,
                    'resizable=yes',
                    'scrollbars=yes',
                    'status=no',
                    'toolbar=no',
                    'menubar=no',
                    'location=yes'
                ].join(',');

                agendaWindow = window.open(AGENDA_URL, 'AgendaJuridicaPopup', features);

                if (agendaWindow) {
                    document.getElementById('cwWindowOpenIndicator').classList.add('show');
                    agendaWindow.focus();
                    startWindowMonitor();
                } else {
                    alert('Pop-up bloqueado pelo navegador. Libere o bloqueador de pop-ups ou clique em "Nova Aba".');
                }
            }

            function startWindowMonitor() {
                clearInterval(agendaCheckInterval);
                agendaCheckInterval = setInterval(function () {
                    if (agendaWindow && agendaWindow.closed) {
                        clearInterval(agendaCheckInterval);
                        agendaWindow = null;
                        document.getElementById('cwWindowOpenIndicator').classList.remove('show');
                    }
                }, 800);
            }
        </script>
    @endpush
</x-admin::layouts>
