<x-admin::layouts>
    <x-slot:title>Dashboard Financeiro</x-slot>

    {{-- ═══════════════════════════════════════════════════════════
         CSS — injected into <head> via @stack('styles') to avoid
         Vue.js app.mount("#app") DOM replacement conflict.
    ═══════════════════════════════════════════════════════════ --}}
    @push('styles')
    <style>
        /* ─── Dashboard Container ─────────────────────────────── */
        .fd { padding:1.5rem; display:flex; flex-direction:column; gap:1.75rem; }

        /* ─── Panel (white section box) ───────────────────────── */
        .fd-pnl { background:#fff; border-radius:14px; border:1px solid #e5e7eb; box-shadow:0 1px 5px rgba(0,0,0,.04); overflow:hidden; }
        .fd-pnl-hd { display:flex; align-items:center; gap:.55rem; padding:.85rem 1.35rem; border-bottom:1px solid #f1f5f9; background:#fafbfd; }
        .fd-pnl-hd-icon { font-size:1.05rem; }
        .fd-pnl-hd-title { font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.07em; margin:0; }
        .fd-pnl-hd-badge { margin-left:auto; font-size:.65rem; font-weight:600; color:#94a3b8; background:#eef2f9; padding:2px 9px; border-radius:99px; }
        .fd-pnl-bd { padding:1.35rem; }

        /* ─── Top bar ─────────────────────────────────────────── */
        .fd-top { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; padding:1rem 1.35rem; }
        .fd-top h1 { font-size:1.25rem; font-weight:800; color:#1e293b; margin:0; }
        .fd-top-sub { font-size:.75rem; color:#94a3b8; margin-top:2px; }
        .fd-sel { font-size:.78rem; border:1px solid #d1d9e6; border-radius:8px; padding:.4rem .75rem; background:#f8fafd; color:#334155; outline:none; cursor:pointer; }
        .fd-sel:focus { border-color:#818cf8; }
        .fd-user-tag { font-size:.78rem; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; padding:.35rem .8rem; }

        /* ─── KPI grid ────────────────────────────────────────── */
        .fd-kpi-g { display:grid; gap:1rem; grid-template-columns:repeat(5,1fr); }
        @media(max-width:1100px){ .fd-kpi-g { grid-template-columns:repeat(3,1fr); } }
        @media(max-width:650px) { .fd-kpi-g { grid-template-columns:repeat(2,1fr); } }

        .fd-kpi { border-radius:12px; padding:1.1rem 1.15rem .95rem; border:1px solid transparent; display:flex; flex-direction:column; gap:.3rem; }
        .fd-kpi-ic { font-size:1.2rem; margin-bottom:2px; }
        .fd-kpi-lb { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; }
        .fd-kpi-vl { font-size:1.3rem; font-weight:800; }
        .fd-kpi-nt { font-size:.65rem; }

        /* pastel skins */
        .fd-g { background:#ecfdf5; border-color:#bbf7d0; }
        .fd-g .fd-kpi-lb,.fd-g .fd-kpi-nt { color:#166534; } .fd-g .fd-kpi-vl { color:#16a34a; }
        .fd-r { background:#fff1f2; border-color:#fecdd3; }
        .fd-r .fd-kpi-lb,.fd-r .fd-kpi-nt { color:#881337; } .fd-r .fd-kpi-vl { color:#dc2626; }
        .fd-b { background:#eff6ff; border-color:#bfdbfe; }
        .fd-b .fd-kpi-lb,.fd-b .fd-kpi-nt { color:#1e3a5f; } .fd-b .fd-kpi-vl { color:#2563eb; }
        .fd-p { background:#f5f3ff; border-color:#ddd6fe; }
        .fd-p .fd-kpi-lb,.fd-p .fd-kpi-nt { color:#4c1d95; } .fd-p .fd-kpi-vl { color:#7c3aed; }
        .fd-a { background:#fffbeb; border-color:#fde68a; }
        .fd-a .fd-kpi-lb,.fd-a .fd-kpi-nt { color:#78350f; } .fd-a .fd-kpi-vl { color:#d97706; }
        .fd-o .fd-kpi-vl { color:#ea580c !important; }

        /* progress bar */
        .fd-bar { height:4px; background:rgba(0,0,0,.08); border-radius:99px; overflow:hidden; margin-top:4px; }
        .fd-bar-f { height:100%; border-radius:99px; }

        /* ─── Charts ──────────────────────────────────────────── */
        .fd-ch-g { display:grid; gap:1.25rem; grid-template-columns:1.6fr 1fr; }
        @media(max-width:900px){ .fd-ch-g { grid-template-columns:1fr; } }
        .fd-ch-ttl { font-size:.78rem; font-weight:700; color:#475569; margin:0 0 .85rem; }
        .fd-ch-wrap { position:relative; height:260px; }

        /* ─── Performance ─────────────────────────────────────── */
        .fd-pf-g { display:grid; gap:1rem; grid-template-columns:1fr 1fr; }
        @media(max-width:650px){ .fd-pf-g { grid-template-columns:1fr; } }
        .fd-pf { background:#f8fafd; border:1px solid #e5e7eb; border-radius:12px; padding:1.1rem 1.2rem; }
        .fd-pf-lb { font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#64748b; margin:0; }
        .fd-pf-vl { font-size:1.4rem; font-weight:800; margin:.35rem 0 0; }
        .fd-pf-ht { font-size:.7rem; color:#94a3b8; margin-top:4px; }

        /* ─── Aging ───────────────────────────────────────────── */
        .fd-ag-g { display:grid; gap:1rem; grid-template-columns:repeat(4,1fr); }
        @media(max-width:800px){ .fd-ag-g { grid-template-columns:repeat(2,1fr); } }
        .fd-ag { border-radius:12px; padding:1rem 1.15rem; border:1px solid transparent; display:flex; flex-direction:column; gap:.35rem; }
        .fd-ag-tg { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.1em; padding:2px 8px; border-radius:99px; width:max-content; }
        .fd-ag-vl { font-size:1.15rem; font-weight:800; }
        .fd-ag-sb { font-size:.65rem; opacity:.6; }
    </style>
    @endpush

    {{-- ═══════════════════════════════════════════════════════════
         CONTENT — rendered inside Krayin's admin layout slot.
         NO inline <style> tags here (Vue destroys them on mount).
    ═══════════════════════════════════════════════════════════ --}}
    <div class="fd">

        {{-- ── HEADER ── --}}
        <div class="fd-pnl">
            <div class="fd-top">
                <div>
                    <h1>💼 Dashboard Financeiro</h1>
                    <p class="fd-top-sub">Visão consolidada do escritório</p>
                </div>
                @php
                    $authUser = auth()->guard('user')->user() ?? auth()->guard('admin')->user();
                    $isGlobal = $authUser && $authUser->view_permission === 'global';
                @endphp
                @if($isGlobal)
                    <form method="GET">
                        <select class="fd-sel" name="responsible_id" onchange="this.form.submit()">
                            <option value="">👤 Todos os Advogados</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('responsible_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </form>
                @elseif($authUser)
                    <span class="fd-user-tag">👤 {{ $authUser->name }}</span>
                @endif
            </div>
        </div>

        {{-- ── SECTION 1: RESUMO FINANCEIRO ── --}}
        <div class="fd-pnl">
            <div class="fd-pnl-hd">
                <span class="fd-pnl-hd-icon">💰</span>
                <h2 class="fd-pnl-hd-title">Resumo Financeiro</h2>
                <span class="fd-pnl-hd-badge">KPIs</span>
            </div>
            <div class="fd-pnl-bd">
                <div class="fd-kpi-g">

                    <div class="fd-kpi fd-g">
                        <span class="fd-kpi-ic">📈</span>
                        <span class="fd-kpi-lb">Total Receitas</span>
                        <span class="fd-kpi-vl">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</span>
                        <span class="fd-kpi-nt">Lançamentos ativos</span>
                    </div>

                    <div class="fd-kpi fd-r">
                        <span class="fd-kpi-ic">📉</span>
                        <span class="fd-kpi-lb">Total Despesas</span>
                        <span class="fd-kpi-vl">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</span>
                        <span class="fd-kpi-nt">Lançamentos ativos</span>
                    </div>

                    <div class="fd-kpi fd-b {{ $saldoLiquido < 0 ? 'fd-o' : '' }}">
                        <span class="fd-kpi-ic">⚖️</span>
                        <span class="fd-kpi-lb">Saldo Líquido</span>
                        <span class="fd-kpi-vl">R$ {{ number_format($saldoLiquido, 2, ',', '.') }}</span>
                        <span class="fd-kpi-nt">Receitas − Despesas</span>
                    </div>

                    <div class="fd-kpi fd-p">
                        <span class="fd-kpi-ic">📊</span>
                        <span class="fd-kpi-lb">Margem de Lucro</span>
                        <span class="fd-kpi-vl">{{ number_format($margemPercent, 1, ',', '.') }}%</span>
                        <div class="fd-bar"><div class="fd-bar-f" style="width:{{ min(100,max(0,$margemPercent)) }}%;background:#7c3aed;"></div></div>
                    </div>

                    <div class="fd-kpi fd-a">
                        <span class="fd-kpi-ic">⏳</span>
                        <span class="fd-kpi-lb">A Receber</span>
                        <span class="fd-kpi-vl">R$ {{ number_format($pendenteReceber, 2, ',', '.') }}</span>
                        <span class="fd-kpi-nt">Receitas pendentes</span>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── SECTION 2: GRÁFICOS ── --}}
        <div class="fd-pnl">
            <div class="fd-pnl-hd">
                <span class="fd-pnl-hd-icon">📉</span>
                <h2 class="fd-pnl-hd-title">Análise Gráfica</h2>
                <span class="fd-pnl-hd-badge">Últimos 6 meses</span>
            </div>
            <div class="fd-pnl-bd">
                <div class="fd-ch-g">
                    <div>
                        <p class="fd-ch-ttl">📊 Receitas vs Despesas — por Mês</p>
                        <div class="fd-ch-wrap"><canvas id="chart-monthly"></canvas></div>
                    </div>
                    <div>
                        <p class="fd-ch-ttl">💳 Formas de Pagamento</p>
                        <div class="fd-ch-wrap"><canvas id="chart-payment"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SECTION 3: INDICADORES DE DESEMPENHO ── --}}
        <div class="fd-pnl">
            <div class="fd-pnl-hd">
                <span class="fd-pnl-hd-icon">🎯</span>
                <h2 class="fd-pnl-hd-title">Indicadores de Desempenho</h2>
            </div>
            <div class="fd-pnl-bd">
                <div class="fd-pf-g">
                    @php $cr = $collectionRate; @endphp
                    <div class="fd-pf">
                        <p class="fd-pf-lb">Taxa de Recebimento</p>
                        <p class="fd-pf-vl" style="color:{{ $cr >= 80 ? '#16a34a' : ($cr >= 50 ? '#d97706' : '#dc2626') }}">{{ number_format($cr, 1, ',', '.') }}%</p>
                        <div class="fd-bar" style="margin-top:8px"><div class="fd-bar-f" style="width:{{ min(100,$cr) }}%;background:{{ $cr >= 80 ? '#16a34a' : ($cr >= 50 ? '#d97706' : '#dc2626') }}"></div></div>
                        <p class="fd-pf-ht">Receitas pagas / total receitas</p>
                    </div>
                    @php $d = $dso; @endphp
                    <div class="fd-pf">
                        <p class="fd-pf-lb">Prazo Médio de Recebimento (DSO)</p>
                        <p class="fd-pf-vl" style="color:{{ $d <= 30 ? '#16a34a' : ($d <= 60 ? '#d97706' : '#dc2626') }}">{{ number_format($d, 0, ',', '.') }} dias</p>
                        <p class="fd-pf-ht">{{ $d <= 30 ? '✅ Ótimo' : ($d <= 60 ? '⚠️ Atenção' : '🔴 Crítico') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── SECTION 4: AGING ── --}}
        <div class="fd-pnl">
            <div class="fd-pnl-hd">
                <span class="fd-pnl-hd-icon">🕐</span>
                <h2 class="fd-pnl-hd-title">Aging de Recebíveis</h2>
                <span class="fd-pnl-hd-badge">por faixa</span>
            </div>
            <div class="fd-pnl-bd">
                <div class="fd-ag-g">
                    <div class="fd-ag" style="background:#ecfdf5;border-color:#a7f3d0">
                        <span class="fd-ag-tg" style="background:#bbf7d0;color:#14532d">0 – 30 dias</span>
                        <span class="fd-ag-vl" style="color:#15803d">R$ {{ number_format($aging['0_30'] ?? 0, 2, ',', '.') }}</span>
                        <span class="fd-ag-sb" style="color:#166534">Correntes</span>
                    </div>
                    <div class="fd-ag" style="background:#fffbeb;border-color:#fde68a">
                        <span class="fd-ag-tg" style="background:#fde68a;color:#78350f">31 – 60 dias</span>
                        <span class="fd-ag-vl" style="color:#b45309">R$ {{ number_format($aging['31_60'] ?? 0, 2, ',', '.') }}</span>
                        <span class="fd-ag-sb" style="color:#92400e">Atenção</span>
                    </div>
                    <div class="fd-ag" style="background:#fff7ed;border-color:#fed7aa">
                        <span class="fd-ag-tg" style="background:#fed7aa;color:#7c2d12">61 – 90 dias</span>
                        <span class="fd-ag-vl" style="color:#c2410c">R$ {{ number_format($aging['61_90'] ?? 0, 2, ',', '.') }}</span>
                        <span class="fd-ag-sb" style="color:#c2410c">Risco</span>
                    </div>
                    <div class="fd-ag" style="background:#fff1f2;border-color:#fecdd3">
                        <span class="fd-ag-tg" style="background:#fecdd3;color:#7f1d1d">&gt; 90 dias</span>
                        <span class="fd-ag-vl" style="color:#b91c1c">R$ {{ number_format($aging['over_90'] ?? 0, 2, ',', '.') }}</span>
                        <span class="fd-ag-sb" style="color:#b91c1c">Crítico</span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        // Wait for Vue to finish mounting, then draw charts
        window.addEventListener("load", function () {
            setTimeout(() => {
                const ctxBar = document.getElementById('chart-monthly');
                const ctxPie = document.getElementById('chart-payment');

                // Bar Chart
                const monthlyData = @json($monthlyData);
                if (ctxBar && monthlyData && monthlyData.length > 0) {
                    new Chart(ctxBar, {
                        type: 'bar',
                        data: {
                            labels: monthlyData.map(d => d.month),
                            datasets: [
                                { label:'Receitas', data:monthlyData.map(d=>d.receitas), backgroundColor:'rgba(134,239,172,.8)', borderColor:'#16a34a', borderWidth:1.5, borderRadius:6 },
                                { label:'Despesas', data:monthlyData.map(d=>d.despesas), backgroundColor:'rgba(252,165,165,.8)', borderColor:'#dc2626', borderWidth:1.5, borderRadius:6 }
                            ]
                        },
                        options: {
                            responsive:true, maintainAspectRatio:false,
                            plugins: {
                                legend: { position:'top', labels:{ usePointStyle:true, padding:16, font:{size:11} } },
                                tooltip: { callbacks: { label: c=>' R$ '+c.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } }
                            },
                            scales: {
                                x: { grid:{display:false}, ticks:{font:{size:10}} },
                                y: { beginAtZero:true, grid:{color:'rgba(0,0,0,.05)'}, ticks:{ font:{size:10}, callback:v=>'R$ '+v.toLocaleString('pt-BR') } }
                            }
                        }
                    });
                } else if (ctxBar) {
                    ctxBar.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:.8rem;font-style:italic">Nenhum dado nos últimos 6 meses.</div>';
                }

                // Doughnut
                const paymentData = @json($paymentDistribution);
                const pLabels = Object.keys(paymentData);
                const pValues = Object.values(paymentData);
                if (ctxPie && pLabels.length > 0) {
                    new Chart(ctxPie, {
                        type: 'doughnut',
                        data: {
                            labels: pLabels,
                            datasets: [{ data:pValues, backgroundColor:['#818cf8','#34d399','#fb923c','#fbbf24','#f472b6','#a78bfa','#94a3b8'].slice(0,pLabels.length), borderWidth:2, borderColor:'#fff', hoverOffset:10 }]
                        },
                        options: { responsive:true, maintainAspectRatio:false, cutout:'62%', plugins:{ legend:{ position:'bottom', labels:{ usePointStyle:true, padding:14, font:{size:11} }} }}
                    });
                } else if (ctxPie) {
                    ctxPie.parentElement.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:.8rem;font-style:italic">Nenhuma cobrança registrada.</div>';
                }
            }, 300);
        });
    </script>
    @endpush

</x-admin::layouts>