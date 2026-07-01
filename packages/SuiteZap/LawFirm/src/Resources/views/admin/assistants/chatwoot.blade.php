<x-admin::layouts>
    <x-slot:title>
        SAC / Atendimento — Chatwoot
    </x-slot:title>

    @push('styles')
        <style>
            /* ── Variáveis de Cor ── */
            :root {
                --cw-teal-100: #ccfbf1;
                --cw-teal-400: #2dd4bf;
                --cw-teal-500: #14b8a6;
                --cw-teal-600: #0d9488;
                --cw-teal-700: #0f766e;
                --cw-teal-800: #115e59;
            }

            /* ── Layout Principal ── */
            .chatwoot-page {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                padding-bottom: 2rem;
            }

            /* ── Hero Card ── */
            .chatwoot-hero {
                position: relative;
                overflow: hidden;
                border-radius: 1rem;
                background: linear-gradient(135deg, #134e4a 0%, #0f766e 40%, #0d9488 70%, #14b8a6 100%);
                padding: 3rem 2.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
                gap: 1.5rem;
                box-shadow: 0 20px 60px rgba(13, 148, 136, 0.35), 0 4px 16px rgba(0,0,0,0.12);
            }

            /* Bolhas decorativas */
            .chatwoot-hero::before {
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

            .chatwoot-hero::after {
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

            .chatwoot-hero-logo {
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

            .chatwoot-hero-title {
                font-size: 2rem;
                font-weight: 800;
                color: #ffffff;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            .chatwoot-hero-subtitle {
                font-size: 1rem;
                color: rgba(255,255,255,0.75);
                max-width: 480px;
                line-height: 1.6;
            }

            /* ── Status Badge ── */
            .chatwoot-status {
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

            .chatwoot-status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #4ade80;
                box-shadow: 0 0 10px #4ade80;
                animation: statusPulse 2s infinite;
            }

            .chatwoot-status-dot.inactive {
                background: #f59e0b;
                box-shadow: 0 0 10px #f59e0b;
                animation: none;
            }

            @keyframes statusPulse {
                0%, 100% { opacity: 1; transform: scale(1); }
                50%       { opacity: 0.5; transform: scale(1.3); }
            }

            /* ── Botões ── */
            .chatwoot-actions {
                display: flex;
                gap: 0.75rem;
                flex-wrap: wrap;
                justify-content: center;
                position: relative;
                z-index: 2;
            }

            .chatwoot-btn-main {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.85rem 2rem;
                background: #ffffff;
                color: var(--cw-teal-700);
                font-size: 0.95rem;
                font-weight: 700;
                border-radius: 0.75rem;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                text-decoration: none;
            }

            .chatwoot-btn-main:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                color: var(--cw-teal-800);
            }

            .chatwoot-btn-secondary {
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

            .chatwoot-btn-secondary:hover {
                background: rgba(255,255,255,0.2);
                transform: translateY(-2px);
                color: #ffffff;
            }

            /* ── Cards de Info ── */
            .chatwoot-info-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 1rem;
            }

            .chatwoot-info-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                padding: 1.25rem;
                display: flex;
                gap: 1rem;
                align-items: flex-start;
                transition: box-shadow 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
            }

            .dark .chatwoot-info-card {
                background: #111827;
                border-color: #1f2937;
            }

            .chatwoot-info-card:hover {
                border-color: #99f6e4;
                box-shadow: 0 4px 16px rgba(13, 148, 136, 0.08);
                transform: translateY(-2px);
            }

            .dark .chatwoot-info-card:hover {
                border-color: var(--cw-teal-700);
            }

            .chatwoot-info-icon {
                flex-shrink: 0;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.625rem;
                background: var(--cw-teal-100);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }

            .dark .chatwoot-info-icon {
                background: rgba(13, 148, 136, 0.15);
            }

            .chatwoot-info-label {
                font-size: 0.8rem;
                font-weight: 600;
                color: #6b7280;
                margin-bottom: 0.2rem;
            }

            .dark .chatwoot-info-label {
                color: #9ca3af;
            }

            .chatwoot-info-value {
                font-size: 0.9rem;
                color: #111827;
                font-weight: 500;
                line-height: 1.4;
            }

            .dark .chatwoot-info-value {
                color: #f3f4f6;
            }

            /* ── Credenciais Card ── */
            .cw-credentials-card {
                background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
                border: 1px solid #99f6e4;
                border-radius: 0.75rem;
                padding: 1.25rem 1.5rem;
                display: flex;
                align-items: flex-start;
                gap: 1rem;
            }

            .dark .cw-credentials-card {
                background: rgba(13, 148, 136, 0.08);
                border-color: rgba(45, 212, 191, 0.25);
            }

            .cw-credentials-icon {
                flex-shrink: 0;
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.625rem;
                background: var(--cw-teal-500);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
            }

            .cw-credentials-title {
                font-size: 0.875rem;
                font-weight: 700;
                color: var(--cw-teal-800);
                margin-bottom: 0.35rem;
            }

            .dark .cw-credentials-title {
                color: var(--cw-teal-400);
            }

            .cw-credentials-detail {
                font-size: 0.8rem;
                color: #374151;
                line-height: 1.6;
            }

            .dark .cw-credentials-detail {
                color: #d1d5db;
            }

            .cw-credentials-detail code {
                background: rgba(13, 148, 136, 0.1);
                border-radius: 0.25rem;
                padding: 0.1rem 0.35rem;
                font-size: 0.78rem;
                color: var(--cw-teal-700);
                font-family: monospace;
            }

            .dark .cw-credentials-detail code {
                background: rgba(45, 212, 191, 0.15);
                color: var(--cw-teal-400);
            }

            /* ── Alerta de Popup Bloqueado ── */
            .chatwoot-alert {
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

            .dark .chatwoot-alert {
                background: rgba(251, 191, 36, 0.08);
                border-color: rgba(251, 191, 36, 0.25);
                color: #fcd34d;
            }

            .chatwoot-alert.show { display: flex; }

            /* ── Indicador de janela aberta ── */
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

            /* ── Responsivo ── */
            @media (max-width: 640px) {
                .chatwoot-hero { padding: 2rem 1.25rem; }
                .chatwoot-hero-title { font-size: 1.5rem; }
            }
        </style>
    @endpush

    <div class="chatwoot-page">

        {{-- ── Hero Principal ── --}}
        <div class="chatwoot-hero">
            <div class="chatwoot-hero-logo">💬</div>

            <div>
                <div class="chatwoot-hero-title">SAC / Atendimento</div>
                <div class="chatwoot-hero-subtitle mt-2">
                    Central de atendimento ao cliente via WhatsApp, e-mail e chat. Gerencie todas as conversas em um único painel.
                </div>
            </div>

            {{-- Status dinâmico --}}
            <div id="cwStatusBadge" class="chatwoot-status">
                <span class="chatwoot-status-dot" id="cwStatusDot"></span>
                <span id="cwStatusText">Clique para abrir o atendimento</span>
            </div>

            {{-- Ações --}}
            <div class="chatwoot-actions">
                <button type="button" id="btnOpenChatwoot" class="chatwoot-btn-main" onclick="launchChatwoot()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                    </svg>
                    Abrir Central de Atendimento
                </button>

                <a href="{{ $chatwootUrl }}" target="_blank" class="chatwoot-btn-secondary">
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
            <span>Atendimento aberto em janela separada — clique em <strong>Abrir Central de Atendimento</strong> para trazer o foco de volta.</span>
        </div>

        {{-- ── Alerta de popup bloqueado ── --}}
        <div class="chatwoot-alert" id="cwPopupBlockedAlert">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="flex-shrink:0; margin-top:2px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
            </svg>
            <div>
                <strong>Pop-up bloqueado pelo navegador.</strong> Para liberar, clique no ícone de bloqueio de pop-ups na barra de endereço, selecione <em>"Sempre permitir pop-ups"</em> para este site e clique novamente em <strong>Abrir Central de Atendimento</strong>.<br>
                <span class="mt-1 inline-block">Ou use o botão <strong>Nova Aba</strong> acima para abrir diretamente.</span>
            </div>
        </div>

        {{-- ── Credenciais de acesso ── --}}
        <div class="cw-credentials-card">
            <div class="cw-credentials-icon">🔑</div>
            <div>
                <div class="cw-credentials-title">Credenciais de Acesso — SAC SuiteZap</div>
                <div class="cw-credentials-detail">
                    Login automático configurado para: <code>{{ $sacEmail }}</code><br>
                    A janela tentará realizar o login automaticamente ao abrir. Caso seja solicitado, use as credenciais acima.
                </div>
            </div>
        </div>

        {{-- ── Cards de Informação ── --}}
        <div class="chatwoot-info-grid">
            <div class="chatwoot-info-card">
                <div class="chatwoot-info-icon">💬</div>
                <div>
                    <div class="chatwoot-info-label">Atendimento Omnichannel</div>
                    <div class="chatwoot-info-value">Gerencie conversas de WhatsApp, e-mail e web chat em um único lugar</div>
                </div>
            </div>
            <div class="chatwoot-info-card">
                <div class="chatwoot-info-icon">🤖</div>
                <div>
                    <div class="chatwoot-info-label">Respostas Automáticas</div>
                    <div class="chatwoot-info-value">Fluxos de atendimento inteligentes com bots e automações configuráveis</div>
                </div>
            </div>
            <div class="chatwoot-info-card">
                <div class="chatwoot-info-icon">📊</div>
                <div>
                    <div class="chatwoot-info-label">Relatórios e Métricas</div>
                    <div class="chatwoot-info-value">Acompanhe volume, tempo de resposta e satisfação dos clientes</div>
                </div>
            </div>
            <div class="chatwoot-info-card">
                <div class="chatwoot-info-icon">🚀</div>
                <div>
                    <div class="chatwoot-info-label">Como Usar</div>
                    <div class="chatwoot-info-value">Clique em <strong>Abrir Central de Atendimento</strong> para iniciar uma sessão em janela dedicada</div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            // ── Configurações ─────────────────────────────────────────
            var CW_URL      = '{{ $chatwootUrl }}';
            var CW_EMAIL    = '{{ $sacEmail }}';
            var CW_PASSWORD = '{{ $sacPassword }}';

            var cwWindow       = null;
            var cwCheckInterval = null;

            /**
             * Tenta fazer auto-login no Chatwoot via localStorage.
             * O Chatwoot armazena a sessão em "user" dentro do localStorage
             * do domínio. Como são domínios diferentes, a injeção direta via
             * postMessage não é possível por segurança (CORS/Same-Origin).
             *
             * Estratégia adotada (safe-fallback):
             * 1. Abre o popup do Chatwoot
             * 2. Aguarda o carregamento da página (onload ou polling)
             * 3. Tenta preencher e submeter o formulário de login via postMessage
             *    (funciona quando o Chatwoot aceita mensagens do pai — depende da config)
             * 4. Como fallback, o usuário faz o login manualmente com as credenciais exibidas
             */
            function launchChatwoot() {
                // Se já aberta, focar
                if (cwWindow && !cwWindow.closed) {
                    cwWindow.focus();
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

                cwWindow = window.open(CW_URL, 'ChatwootSACPopup', features);

                if (!cwWindow || cwWindow.closed || typeof cwWindow.closed === 'undefined') {
                    setStatus('inactive', 'Pop-up bloqueado pelo navegador');
                    document.getElementById('cwPopupBlockedAlert').classList.add('show');
                    document.getElementById('cwWindowOpenIndicator').classList.remove('show');
                    return;
                }

                // Popup abriu com sucesso
                document.getElementById('cwPopupBlockedAlert').classList.remove('show');
                document.getElementById('cwWindowOpenIndicator').classList.add('show');
                setStatus('active', 'Atendimento aberto — janela ativa');
                cwWindow.focus();

                // Monitorar fechamento
                startWindowMonitor();

                // Tentativa de auto-login via postMessage após carregamento
                attemptAutoLogin();
            }

            /**
             * Tenta preencher o formulário de login via postMessage.
             * O Chatwoot expõe uma janela acessível — aguardamos o carregamento
             * e então tentamos interagir com o formulário de login via execução
             * de script no contexto da janela filha.
             *
             * NOTA: Isso só funciona se o Chatwoot estiver no mesmo domínio
             * ou se a política CORS/CSP permitir. Para domínios externos
             * (cross-origin), o browser bloqueia acesso ao `document` da janela filha.
             * Neste caso, a view exibe as credenciais para o usuário realizar login manual.
             */
            function attemptAutoLogin() {
                var attempts  = 0;
                var maxAttempts = 15; // 15 tentativas a cada 1s = 15s de timeout

                var loginInterval = setInterval(function () {
                    attempts++;

                    if (!cwWindow || cwWindow.closed) {
                        clearInterval(loginInterval);
                        return;
                    }

                    if (attempts > maxAttempts) {
                        clearInterval(loginInterval);
                        return;
                    }

                    try {
                        // Tentativa de acesso ao documento (apenas funciona se same-origin)
                        var cwDoc = cwWindow.document;
                        if (!cwDoc) return;

                        // Procura o formulário de login do Chatwoot (seletor padrão)
                        var emailInput    = cwDoc.querySelector('input[type="email"], input[name="email"]');
                        var passwordInput = cwDoc.querySelector('input[type="password"], input[name="password"]');
                        var submitBtn     = cwDoc.querySelector('button[type="submit"], [data-key="login-submit"]');

                        if (emailInput && passwordInput && submitBtn) {
                            clearInterval(loginInterval);

                            // Preenche as credenciais usando o setter nativo
                            // (necessário para frameworks reativos como Vue/React)
                            function setNativeValue(element, value) {
                                var nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                                    window.HTMLInputElement.prototype, 'value'
                                ).set;
                                nativeInputValueSetter.call(element, value);
                                element.dispatchEvent(new Event('input', { bubbles: true }));
                                element.dispatchEvent(new Event('change', { bubbles: true }));
                            }

                            setNativeValue(emailInput, CW_EMAIL);
                            setNativeValue(passwordInput, CW_PASSWORD);

                            // Aguarda um tick para o Vue/React processar o input
                            setTimeout(function () {
                                submitBtn.click();
                                setStatus('active', 'Login realizado automaticamente ✓');
                            }, 300);
                        }
                    } catch (e) {
                        // Cross-origin block — esperado para domínios externos
                        // O usuário precisará fazer login manualmente
                        if (attempts === maxAttempts) {
                            clearInterval(loginInterval);
                        }
                    }
                }, 1000);
            }

            /**
             * Monitora o estado da janela do popup
             */
            function startWindowMonitor() {
                clearInterval(cwCheckInterval);
                cwCheckInterval = setInterval(function () {
                    if (cwWindow && cwWindow.closed) {
                        clearInterval(cwCheckInterval);
                        cwWindow = null;
                        setStatus('inactive', 'Atendimento encerrado — clique para reabrir');
                        document.getElementById('cwWindowOpenIndicator').classList.remove('show');
                    }
                }, 800);
            }

            /**
             * Atualiza o badge de status no hero
             */
            function setStatus(state, text) {
                var dot  = document.getElementById('cwStatusDot');
                var span = document.getElementById('cwStatusText');
                if (dot)  dot.className  = 'chatwoot-status-dot' + (state === 'inactive' ? ' inactive' : '');
                if (span) span.textContent = text;
            }

            // Inicializa o status ao carregar
            document.addEventListener('DOMContentLoaded', function () {
                setStatus('inactive', 'Clique para abrir o atendimento');
            });
        </script>
    @endpush
</x-admin::layouts>
