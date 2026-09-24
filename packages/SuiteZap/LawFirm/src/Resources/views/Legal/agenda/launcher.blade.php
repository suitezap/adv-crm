<x-admin::layouts>
    <x-slot:title>
        Agenda Jurídica
    </x-slot:title>

    @push('styles')
        <style>
            :root {
                --agenda-color-100: #e0e7ff;
                --agenda-color-500: #6366f1;
                --agenda-color-700: #4338ca;
            }

            .agenda-page {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                padding-bottom: 2rem;
            }

            .agenda-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                background: linear-gradient(135deg, #312e81 0%, #4338ca 40%, #4f46e5 70%, #6366f1 100%);
                padding: 3rem 2.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 1.5rem;
                box-shadow: 0 20px 60px rgba(79, 70, 229, 0.35), 0 4px 16px rgba(0,0,0,0.12);
            }

            .agenda-hero::before {
                content: '';
                position: absolute;
                top: -80px;
                right: -80px;
                width: 300px;
                height: 300px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.05);
                pointer-events: none;
            }

            .agenda-hero::after {
                content: '';
                position: absolute;
                bottom: -60px;
                left: -60px;
                width: 240px;
                height: 240px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.04);
                pointer-events: none;
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
                padding: 0.85rem 2rem;
                background: #ffffff;
                color: var(--agenda-color-700);
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
                padding: 0.85rem 1.5rem;
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

            .window-open-indicator {
                display: none;
                align-items: center;
                justify-content: center;
                gap: 1rem;
                padding: 1.25rem 1.5rem;
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                border: 1px solid #86efac;
                border-radius: 0.75rem;
                font-size: 0.9rem;
                color: #15803d;
                font-weight: 500;
            }

            .window-open-indicator.show { display: flex; }
            .window-indicator-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #22c55e;
                box-shadow: 0 0 12px #22c55e;
                animation: statusPulse 2s infinite;
                flex-shrink: 0;
            }

            @keyframes statusPulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50%       { opacity: 0.5; transform: scale(1.3); }
            }
        </style>
    @endpush

    <div class="agenda-page">
        <div class="agenda-hero">
            <h1 style="color: white; font-size: 2.2rem; font-weight: bold; margin-bottom: 0;">Agenda Jurídica</h1>
            <p style="color: rgba(255,255,255,0.8); margin-bottom: 1rem;">Gerencie seus prazos e compromissos com mais espaço e organização.</p>
            
            <div class="agenda-actions">
                <button type="button" class="agenda-btn-main" onclick="launchAgenda()">
                    <i class="icon-calendar text-2xl"></i>
                    Abrir em Nova Janela (Popup)
                </button>

                <a href="{{ route('admin.lawfirm.agenda.viewer') }}?clean=1" target="_blank" class="agenda-btn-secondary">
                    Abrir em Nova Aba
                </a>
            </div>
        </div>

        <div class="window-open-indicator" id="cwWindowOpenIndicator">
            <span class="window-indicator-dot"></span>
            <span>A Agenda Jurídica está aberta em uma janela separada.</span>
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
                    alert('Pop-up bloqueado pelo navegador. Libere o bloqueador de pop-ups ou clique em "Abrir em Nova Aba".');
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
