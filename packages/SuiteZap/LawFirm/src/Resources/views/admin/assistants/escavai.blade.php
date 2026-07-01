<x-admin::layouts>
    <x-slot:title>
        EscavAI — Chat Inteligente do Escavador
    </x-slot:title>

    @push('styles')
        <style>
            /* ── Variáveis de Cor ── */
            :root {
                --escav-purple-100: #f3e8ff;
                --escav-purple-400: #c084fc;
                --escav-purple-500: #a855f7;
                --escav-purple-600: #9333ea;
                --escav-purple-700: #7c3aed;
                --escav-purple-800: #6b21a8;
            }

            /* ── Layout Principal ── */
            .escavai-page {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                padding-bottom: 2rem;
            }

            /* ── Hero Card ── */
            .escavai-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                background: linear-gradient(135deg, #4c1d95 0%, #6d28d9 40%, #7c3aed 70%, #9d4edd 100%);
                padding: 3rem 2.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 1.5rem;
                box-shadow: 0 20px 60px rgba(109, 40, 217, 0.35), 0 4px 16px rgba(0,0,0,0.12);
            }

            /* Bolhas decorativas */
            .escavai-hero::before {
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

            .escavai-hero::after {
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

            .escavai-hero-logo {
                width: 80px;
                height: 80px;
                border-radius: 1.25rem;
                background: rgba(255,255,255,0.15);
                backdrop-filter: blur(8px);
                border: 1px solid rgba(255,255,255,0.25);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 2.5rem;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15);
                animation: heroFloat 3s ease-in-out infinite;
            }

            @keyframes heroFloat {
                0%, 100% { transform: translateY(0); }
                50%       { transform: translateY(-6px); }
            }

            .escavai-hero-title {
                font-size: 2rem;
                font-weight: 800;
                color: #ffffff;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            .escavai-hero-subtitle {
                font-size: 1rem;
                color: rgba(255,255,255,0.75);
                max-width: 480px;
                line-height: 1.6;
            }

            /* ── Status Badge Animado ── */
            .escavai-status {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.35rem 1rem;
                border-radius: 9999px;
                background: rgba(255,255,255,0.12);
                backdrop-filter: blur(4px);
                border: 1px solid rgba(255,255,255,0.2);
                font-size: 0.8rem;
                font-weight: 600;
                color: #fff;
            }

            .escavai-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #4ade80;
                box-shadow: 0 0 10px #4ade80;
                animation: statusPulse 2s infinite;
            }

            .escavai-status-dot.inactive {
                background: #f59e0b;
                box-shadow: 0 0 10px #f59e0b;
                animation: none;
            }

            @keyframes statusPulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50%       { opacity: 0.5; transform: scale(1.3); }
            }

            /* ── Botões de Ação ── */
            .escavai-actions {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                justify-content: center;
                position: relative;
                z-index: 2;
            }

            .escavai-btn-main {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.85rem 2rem;
                background: #ffffff;
                color: #7c3aed;
                font-size: 0.95rem;
                font-weight: 700;
                border-radius: 0.75rem;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                text-decoration: none;
            }

            .escavai-btn-main:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                color: #6d28d9;
            }

            .escavai-btn-secondary {
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

            .escavai-btn-secondary:hover {
                background: rgba(255,255,255,0.2);
                transform: translateY(-2px);
                color: #ffffff;
            }

            /* ── Cards de Informação ── */
            .escavai-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 1rem;
            }

            .escavai-info-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                padding: 1.25rem;
                display: flex;
                gap: 1rem;
                align-items: flex-start;
                transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            }

            .dark .escavai-info-card {
                background: #111827;
                border-color: #1f2937;
            }

            .escavai-info-card:hover {
                border-color: #d8b4fe;
                box-shadow: 0 4px 16px rgba(124, 58, 237, 0.08);
                transform: translateY(-2px);
            }

            .dark .escavai-info-card:hover {
                border-color: #6d28d9;
            }

            .escavai-info-icon {
                flex-shrink: 0;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.625rem;
                background: var(--escav-purple-100);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }

            .dark .escavai-info-icon {
                background: rgba(124, 58, 237, 0.15);
            }

            .escavai-info-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #6b7280;
                margin-bottom: 0.2rem;
            }

            .dark .escavai-info-label {
                color: #9ca3af;
            }

            .escavai-info-value {
                font-size: 0.9rem;
                color: #111827;
                font-weight: 500;
                line-height: 1.4;
            }

            .dark .escavai-info-value {
                color: #f3f4f6;
            }

            /* ── Alerta de Popup Bloqueado ── */
            .escavai-alert {
                display: none;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem 1.25rem;
                background: #fffbeb;
                border: 1px solid #fcd34d;
                border-radius: 0.75rem;
                font-size: 0.875rem;
                color: #92400e;
                line-height: 1.5;
            }

            .dark .escavai-alert {
                background: rgba(251, 191, 36, 0.08);
                border-color: rgba(251, 191, 36, 0.25);
                color: #fcd34d;
            }

            .escavai-alert.show {
                display: flex;
            }

            /* ── Indicador de Janela Aberta ── */
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

            .dark .window-open-indicator {
                background: rgba(21, 128, 61, 0.08);
                border-color: rgba(134, 239, 172, 0.25);
                color: #4ade80;
            }

            .window-open-indicator.show {
                display: flex;
            }

            .window-indicator-dot {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: #22c55e;
                box-shadow: 0 0 12px #22c55e;
                animation: statusPulse 2s infinite;
                flex-shrink: 0;
            }

            /* ── Responsivo ── */
            @media (max-width: 640px) {
                .escavai-hero {
                    padding: 2rem 1.25rem;
                }
                .escavai-hero-title {
                    font-size: 1.5rem;
                }
            }
        </style>
    @endpush

    <div class="escavai-page">

        {{-- ── Hero Principal ── --}}
        <div class="escavai-hero">
            <div class="escavai-hero-logo">⚖️</div>

            <div>
                <div class="escavai-hero-title">EscavAI</div>
                <div class="escavai-hero-subtitle mt-2">
                    Chat de inteligência artificial do Escavador para consultas jurídicas, pesquisa de processos e análise de jurisprudência.
                </div>
            </div>

            {{-- Status dinâmico --}}
            <div id="statusBadge" class="escavai-status">
                <span class="escavai-status-dot" id="statusDot"></span>
                <span id="statusText">Iniciando chat...</span>
            </div>

            {{-- Ações --}}
            <div class="escavai-actions">
                <button type="button" id="btnOpenChat" class="escavai-btn-main" onclick="launchChat()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    Abrir Chat EscavAI
                </button>

                <a href="https://escavai.escavador.com/chat" target="_blank" class="escavai-btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                    </svg>
                    Nova Aba
                </a>
            </div>
        </div>

        {{-- ── Indicador de janela aberta ── --}}
        <div class="window-open-indicator" id="windowOpenIndicator">
            <span class="window-indicator-dot"></span>
            <span>Chat EscavAI aberto em janela separada — clique em <strong>Abrir Chat EscavAI</strong> para trazer o foco de volta.</span>
        </div>

        {{-- ── Alerta de popup bloqueado ── --}}
        <div class="escavai-alert" id="popupBlockedAlert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="flex-shrink:0; margin-top:2px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
                <strong>Pop-up bloqueado pelo navegador.</strong> Para liberar, clique no ícone de bloqueio de pop-ups na barra de endereço do seu navegador, selecione <em>"Sempre permitir pop-ups"</em> para este site e depois clique novamente em <strong>Abrir Chat EscavAI</strong>.<br>
                <span class="mt-1 inline-block">Ou use o botão <strong>Nova Aba</strong> acima para abrir diretamente.</span>
            </div>
        </div>

        {{-- ── Cards de Informação ── --}}
        <div class="escavai-info-grid">
            <div class="escavai-info-card">
                <div class="escavai-info-icon">🔍</div>
                <div>
                    <div class="escavai-info-label">Pesquisa Jurídica</div>
                    <div class="escavai-info-value">Consulta inteligente de jurisprudência, doutrina e legislação com IA</div>
                </div>
            </div>
            <div class="escavai-info-card">
                <div class="escavai-info-icon">⚖️</div>
                <div>
                    <div class="escavai-info-label">Análise de Processos</div>
                    <div class="escavai-info-value">Resumo e análise automatizada de peças processuais e movimentações</div>
                </div>
            </div>
            <div class="escavai-info-card">
                <div class="escavai-info-icon">📋</div>
                <div>
                    <div class="escavai-info-label">Minutas e Petições</div>
                    <div class="escavai-info-value">Auxílio na elaboração de documentos jurídicos com sugestões de IA</div>
                </div>
            </div>
            <div class="escavai-info-card">
                <div class="escavai-info-icon">🚀</div>
                <div>
                    <div class="escavai-info-label">Como Usar</div>
                    <div class="escavai-info-value">Clique em <strong>Abrir Chat EscavAI</strong> para iniciar uma sessão em janela dedicada</div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            var ESCAVAI_URL = 'https://escavai.escavador.com/chat';
            var escavaiWindow = null;
            var checkInterval  = null;

            /**
             * Abre o chat em uma janela pop-up centralizada e monitora o estado
             */
            function launchChat() {
                // Se a janela já existe e está aberta, apenas focar
                if (escavaiWindow && !escavaiWindow.closed) {
                    escavaiWindow.focus();
                    return;
                }

                var width  = Math.min(1100, window.screen.availWidth  - 40);
                var height = Math.min(820,  window.screen.availHeight - 60);
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

                escavaiWindow = window.open(ESCAVAI_URL, 'EscavaiChatPopup', features);

                if (!escavaiWindow || escavaiWindow.closed || typeof escavaiWindow.closed === 'undefined') {
                    // Popup foi bloqueado
                    setStatus('inactive', 'Pop-up bloqueado pelo navegador');
                    document.getElementById('popupBlockedAlert').classList.add('show');
                    document.getElementById('windowOpenIndicator').classList.remove('show');
                    return;
                }

                // Popup abriu com sucesso
                document.getElementById('popupBlockedAlert').classList.remove('show');
                document.getElementById('windowOpenIndicator').classList.add('show');
                setStatus('active', 'Chat aberto — janela ativa');
                escavaiWindow.focus();

                // Monitorar fechamento da janela
                startWindowMonitor();
            }

            /**
             * Monitora o estado da janela do popup (aberta/fechada)
             */
            function startWindowMonitor() {
                clearInterval(checkInterval);
                checkInterval = setInterval(function () {
                    if (escavaiWindow && escavaiWindow.closed) {
                        clearInterval(checkInterval);
                        escavaiWindow = null;
                        setStatus('inactive', 'Chat encerrado — clique para reabrir');
                        document.getElementById('windowOpenIndicator').classList.remove('show');
                    }
                }, 800);
            }

            /**
             * Atualiza o badge de status no hero
             */
            function setStatus(state, text) {
                var dot  = document.getElementById('statusDot');
                var span = document.getElementById('statusText');
                if (dot)  dot.className  = 'escavai-status-dot' + (state === 'inactive' ? ' inactive' : '');
                if (span) span.textContent = text;
            }

            /**
             * Auto-lança o chat ao carregar a página (disparo único com pequeno delay
             * para garantir que o contexto de interação do usuário ainda está ativo)
             */
            document.addEventListener('DOMContentLoaded', function () {
                // Aguarda 600ms para que o layout carregue antes de abrir o popup
                // O navegador permite window.open logo após navegação/clique do usuário
                setTimeout(function () {
                    setStatus('inactive', 'Clique para abrir o chat');
                }, 500);
            });
        </script>
    @endpush
</x-admin::layouts>
