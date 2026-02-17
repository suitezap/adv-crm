@php
    $financeiros = isset($processo) ? $processo->financeiros : collect([]);
    $categoriasReceita = [
        'honorario' => 'Honorários',
        'sucumbencia' => 'Sucumbência',
        'reembolso' => 'Reembolso',
        'consultoria' => 'Consultoria'
    ];
    $categoriasDespesa = [
        'custas' => 'Custas Processuais',
        'deslocamento' => 'Deslocamento',
        'taxas' => 'Taxas',
        'diligencias' => 'Diligências'
    ];
    $formasPagamento = ['boleto' => 'Boleto', 'pix' => 'PIX', 'transferencia' => 'Transferência', 'cartao' => 'Cartão'];
    $startClosed = $startClosed ?? false;
    $readOnly = $readOnly ?? false;

    $totalReceitas = $financeiros->where('tipo', 'receita')->where('status', '!=', 'cancelado')->sum('valor');
    $totalDespesas = $financeiros->where('tipo', 'despesa')->where('status', '!=', 'cancelado')->sum('valor');
    $saldo = $totalReceitas - $totalDespesas;
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900" id="lf-financial-widget" v-pre>

    {{-- HEADER --}}
    <div class="flex items-center justify-between mb-4 cursor-pointer select-none"
         onclick="window.lfFinToggle()">
        <div class="flex items-center gap-4">
            <p class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                Gestão Financeira
                <i id="lf-fin-arrow" class="{{ $startClosed ? 'icon-arrow-down' : 'icon-arrow-up' }} text-gray-500 ml-2 text-lg"></i>
            </p>
            <div class="flex gap-2">
                <span class="px-2 py-1 text-xs rounded-full font-semibold bg-green-100 text-green-700">
                    Rec: <span id="lf-fin-total-rec">R$ {{ number_format($totalReceitas, 2, ',', '.') }}</span>
                </span>
                <span class="px-2 py-1 text-xs rounded-full font-semibold bg-red-100 text-red-700">
                    Desp: <span id="lf-fin-total-desp">R$ {{ number_format($totalDespesas, 2, ',', '.') }}</span>
                </span>
                <span id="lf-fin-saldo-pill" class="px-2 py-1 text-xs rounded-full font-bold {{ $saldo >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                    Saldo: <span id="lf-fin-total-saldo">R$ {{ number_format($saldo, 2, ',', '.') }}</span>
                </span>
            </div>
        </div>

        @if(!$readOnly)
        <div class="flex gap-2" onclick="event.stopPropagation()">
            <button type="button" onclick="window.lfFinSave()" id="lf-fin-btn-save"
                class="flex items-center justify-center gap-2 rounded border border-emerald-600 bg-white px-3 py-1.5 text-emerald-600 hover:bg-emerald-50 transition-colors disabled:opacity-50">
                <span class="icon-save text-lg" id="lf-fin-save-icon"></span>
                <span class="text-sm font-medium" id="lf-fin-save-text">Salvar</span>
            </button>
            <button type="button" onclick="window.lfFinAddRow()" class="primary-button">
                <span class="icon-plus text-lg inline-block align-middle mr-1"></span>
                Novo Lançamento
            </button>
        </div>
        @endif
    </div>

    {{-- CONTENT (collapsible) --}}
    <div id="lf-fin-content" style="{{ $startClosed ? 'display:none' : '' }}">

        {{-- Empty State --}}
        <div id="lf-fin-empty" class="text-center py-8 text-gray-400 border-2 border-dashed border-gray-200 rounded-lg dark:border-gray-700"
            style="{{ $financeiros->count() > 0 ? 'display:none' : '' }}">
            <span class="icon-wallet text-4xl mb-2 block opacity-50"></span>
            <p>Nenhum lançamento financeiro registrado.</p>
            <button type="button" class="text-blue-600 hover:underline mt-2" onclick="window.lfFinAddRow()">Adicionar o primeiro</button>
        </div>

        {{-- Table --}}
        <div id="lf-fin-table-wrap" class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
            style="{{ $financeiros->count() === 0 ? 'display:none' : '' }}">
            <table class="min-w-full text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 min-w-[100px]">Tipo</th>
                        <th class="px-4 py-3 min-w-[140px]">Categoria</th>
                        <th class="px-4 py-3 min-w-[200px]">Descrição</th>
                        <th class="px-4 py-3 min-w-[120px]">Valor</th>
                        <th class="px-4 py-3 w-[140px]">Vencimento</th>
                        <th class="px-4 py-3 w-[120px]">Status</th>
                        <th class="px-4 py-3 w-[130px]">Pgto.</th>
                        @if(!$readOnly)
                            <th class="px-4 py-3 w-[50px] text-center"></th>
                        @endif
                    </tr>
                </thead>
                <tbody id="lf-fin-tbody">
                    @foreach($financeiros as $idx => $fin)
                    <tr class="border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 transition-colors" data-fin-id="{{ $fin->id }}">
                        {{-- Tipo --}}
                        <td class="px-2 py-2">
                            <select data-field="tipo" onchange="window.lfFinOnTipoChange(this)"
                                class="w-full rounded-md border px-2 py-1.5 text-sm {{ $fin->tipo === 'receita' ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200' }}">
                                <option value="receita" {{ $fin->tipo === 'receita' ? 'selected' : '' }}>Receita</option>
                                <option value="despesa" {{ $fin->tipo === 'despesa' ? 'selected' : '' }}>Despesa</option>
                            </select>
                        </td>

                        {{-- Categoria --}}
                        <td class="px-2 py-2">
                            <select data-field="category"
                                class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                                <option value="">- Selecione -</option>
                                @php $cats = $fin->tipo === 'receita' ? $categoriasReceita : $categoriasDespesa; @endphp
                                @foreach($cats as $catKey => $catLabel)
                                    <option value="{{ $catKey }}" {{ $fin->category === $catKey ? 'selected' : '' }}>{{ $catLabel }}</option>
                                @endforeach
                            </select>
                        </td>

                        {{-- Nome --}}
                        <td class="px-2 py-2">
                            <input type="text" data-field="nome" value="{{ $fin->nome }}"
                                class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500"
                                placeholder="Ex: Honorários Iniciais">
                        </td>

                        {{-- Valor --}}
                        <td class="px-2 py-2">
                            <div class="relative">
                                <span class="absolute left-2 top-1.5 text-gray-500">R$</span>
                                <input type="number" step="0.01" data-field="valor" value="{{ $fin->valor }}"
                                    oninput="window.lfFinUpdateTotals()"
                                    class="w-full rounded-md border border-gray-300 pl-8 pr-2 py-1.5 text-sm font-semibold {{ $fin->tipo === 'receita' ? 'text-green-600' : 'text-red-600' }}">
                            </div>
                        </td>

                        {{-- Vencimento --}}
                        <td class="px-2 py-2">
                            <input type="date" data-field="data_vencimento"
                                value="{{ $fin->data_vencimento ? \Carbon\Carbon::parse($fin->data_vencimento)->format('Y-m-d') : '' }}"
                                class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">
                        </td>

                        {{-- Status --}}
                        <td class="px-2 py-2">
                            <select data-field="status" onchange="window.lfFinOnStatusChange(this)"
                                class="w-full rounded-md border px-2 py-1.5 text-sm font-medium
                                @if($fin->status === 'pendente') bg-yellow-50 text-yellow-700 border-yellow-200
                                @elseif($fin->status === 'pago') bg-green-50 text-green-700 border-green-200
                                @else bg-gray-100 text-gray-500 border-gray-200
                                @endif">
                                <option value="pendente" {{ $fin->status === 'pendente' ? 'selected' : '' }}>Pendente</option>
                                <option value="pago" {{ $fin->status === 'pago' ? 'selected' : '' }}>Pago</option>
                                <option value="cancelado" {{ $fin->status === 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                            </select>
                        </td>

                        {{-- Pagamento --}}
                        <td class="px-2 py-2">
                            <div class="flex flex-col gap-1">
                                <select data-field="payment_method"
                                    class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs"
                                    {{ $fin->status !== 'pago' ? 'disabled' : '' }}>
                                    <option value="">- Método -</option>
                                    @foreach($formasPagamento as $pmKey => $pmLabel)
                                        <option value="{{ $pmKey }}" {{ $fin->payment_method === $pmKey ? 'selected' : '' }}>{{ $pmLabel }}</option>
                                    @endforeach
                                </select>
                                <input type="date" data-field="payment_date"
                                    value="{{ $fin->payment_date ? \Carbon\Carbon::parse($fin->payment_date)->format('Y-m-d') : '' }}"
                                    class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs"
                                    {{ $fin->status !== 'pago' ? 'disabled' : '' }}>
                            </div>
                        </td>

                        {{-- Actions --}}
                        @if(!$readOnly)
                        <td class="px-2 py-2 text-center">
                            <input type="hidden" data-field="id" value="{{ $fin->id }}">
                            <button type="button" onclick="window.lfFinDeleteRow(this)"
                                class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors">
                                <span class="icon-delete text-xl"></span>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Validation Errors --}}
        <div id="lf-fin-errors" class="mt-4 p-3 bg-red-50 text-red-700 rounded border border-red-200 text-sm" style="display:none">
            <p class="font-bold">Por favor, corrija os erros:</p>
            <ul id="lf-fin-error-list" class="list-disc pl-5 mt-1"></ul>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    // Config
    var PROCESS_ID = {{ $processo->id }};
    var SAVE_URL = "{{ route('admin.lawfirm.financial.process.store', $processo->id) }}";
    var READ_ONLY = {{ $readOnly ? 'true' : 'false' }};
    var categoriasReceita = {!! json_encode($categoriasReceita) !!};
    var categoriasDespesa = {!! json_encode($categoriasDespesa) !!};
    var formasPagamento = {!! json_encode($formasPagamento) !!};
    var rowCounter = {{ $financeiros->count() + 100 }};

    // ===================== TOGGLE =====================
    window.lfFinToggle = function() {
        var content = document.getElementById('lf-fin-content');
        var arrow = document.getElementById('lf-fin-arrow');
        if (!content) return;
        var isHidden = content.style.display === 'none';
        content.style.display = isHidden ? '' : 'none';
        if (arrow) {
            arrow.className = (isHidden ? 'icon-arrow-up' : 'icon-arrow-down') + ' text-gray-500 ml-2 text-lg';
        }
    };

    // ===================== ADD ROW =====================
    window.lfFinAddRow = function() {
        var tbody = document.getElementById('lf-fin-tbody');
        if (!tbody) return;
        rowCounter++;
        var idx = rowCounter;
        var today = new Date().toISOString().split('T')[0];

        var tr = document.createElement('tr');
        tr.className = 'border-b bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 transition-colors';
        tr.setAttribute('data-fin-id', '');

        var actionsCell = '';
        if (!READ_ONLY) {
            actionsCell = '<td class="px-2 py-2 text-center">' +
                '<input type="hidden" data-field="id" value="">' +
                '<button type="button" onclick="window.lfFinDeleteRow(this)" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition-colors">' +
                '<span class="icon-delete text-xl"></span></button></td>';
        }

        var catOptions = Object.entries(categoriasReceita).map(function(e) {
            return '<option value="' + e[0] + '">' + e[1] + '</option>';
        }).join('');

        var pmOptions = Object.entries(formasPagamento).map(function(e) {
            return '<option value="' + e[0] + '">' + e[1] + '</option>';
        }).join('');

        tr.innerHTML =
            '<td class="px-2 py-2">' +
                '<select data-field="tipo" onchange="window.lfFinOnTipoChange(this)" class="w-full rounded-md border px-2 py-1.5 text-sm text-green-700 bg-green-50 border-green-200">' +
                '<option value="receita" selected>Receita</option><option value="despesa">Despesa</option></select></td>' +
            '<td class="px-2 py-2">' +
                '<select data-field="category" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm">' +
                '<option value="">- Selecione -</option>' + catOptions + '</select></td>' +
            '<td class="px-2 py-2"><input type="text" data-field="nome" value="" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-blue-500" placeholder="Ex: Honorários Iniciais"></td>' +
            '<td class="px-2 py-2"><div class="relative"><span class="absolute left-2 top-1.5 text-gray-500">R$</span>' +
                '<input type="number" step="0.01" data-field="valor" value="" oninput="window.lfFinUpdateTotals()" class="w-full rounded-md border border-gray-300 pl-8 pr-2 py-1.5 text-sm font-semibold text-green-600"></div></td>' +
            '<td class="px-2 py-2"><input type="date" data-field="data_vencimento" value="' + today + '" class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"></td>' +
            '<td class="px-2 py-2"><select data-field="status" onchange="window.lfFinOnStatusChange(this)" class="w-full rounded-md border px-2 py-1.5 text-sm font-medium bg-yellow-50 text-yellow-700 border-yellow-200">' +
                '<option value="pendente" selected>Pendente</option><option value="pago">Pago</option><option value="cancelado">Cancelado</option></select></td>' +
            '<td class="px-2 py-2"><div class="flex flex-col gap-1">' +
                '<select data-field="payment_method" class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs" disabled>' +
                '<option value="">- Método -</option>' + pmOptions + '</select>' +
                '<input type="date" data-field="payment_date" value="" class="w-full rounded-md border border-gray-300 px-1 py-1 text-xs" disabled></div></td>' +
            actionsCell;

        tbody.appendChild(tr);
        lfFinUpdateVisibility();
        window.lfFinUpdateTotals();

        // Open panel if collapsed
        var content = document.getElementById('lf-fin-content');
        if (content && content.style.display === 'none') {
            window.lfFinToggle();
        }
    };

    // ===================== DELETE ROW =====================
    window.lfFinDeleteRow = function(btn) {
        if (!confirm('Tem certeza que deseja remover este lançamento?')) return;
        var tr = btn.closest('tr');
        if (tr) tr.remove();
        lfFinUpdateVisibility();
        window.lfFinUpdateTotals();
    };

    // ===================== TIPO CHANGE =====================
    window.lfFinOnTipoChange = function(sel) {
        var tr = sel.closest('tr');
        if (!tr) return;

        // Update tipo select styling
        if (sel.value === 'receita') {
            sel.className = 'w-full rounded-md border px-2 py-1.5 text-sm text-green-700 bg-green-50 border-green-200';
        } else {
            sel.className = 'w-full rounded-md border px-2 py-1.5 text-sm text-red-700 bg-red-50 border-red-200';
        }

        // Update category options
        var catSel = tr.querySelector('[data-field="category"]');
        var cats = sel.value === 'receita' ? categoriasReceita : categoriasDespesa;
        catSel.innerHTML = '<option value="">- Selecione -</option>' +
            Object.entries(cats).map(function(e) { return '<option value="' + e[0] + '">' + e[1] + '</option>'; }).join('');

        // Update valor color
        var valorInput = tr.querySelector('[data-field="valor"]');
        if (valorInput) {
            valorInput.className = valorInput.className
                .replace('text-green-600', '').replace('text-red-600', '');
            valorInput.classList.add(sel.value === 'receita' ? 'text-green-600' : 'text-red-600');
        }

        window.lfFinUpdateTotals();
    };

    // ===================== STATUS CHANGE =====================
    window.lfFinOnStatusChange = function(sel) {
        var tr = sel.closest('tr');
        if (!tr) return;

        // Update status styling
        sel.className = 'w-full rounded-md border px-2 py-1.5 text-sm font-medium ';
        if (sel.value === 'pendente') sel.className += 'bg-yellow-50 text-yellow-700 border-yellow-200';
        else if (sel.value === 'pago') sel.className += 'bg-green-50 text-green-700 border-green-200';
        else sel.className += 'bg-gray-100 text-gray-500 border-gray-200';

        // Enable/disable payment fields
        var pmMethod = tr.querySelector('[data-field="payment_method"]');
        var pmDate = tr.querySelector('[data-field="payment_date"]');
        if (pmMethod) pmMethod.disabled = sel.value !== 'pago';
        if (pmDate) pmDate.disabled = sel.value !== 'pago';

        if (sel.value === 'pago' && pmDate && !pmDate.value) {
            pmDate.value = new Date().toISOString().split('T')[0];
        }

        window.lfFinUpdateTotals();
    };

    // ===================== UPDATE TOTALS =====================
    window.lfFinUpdateTotals = function() {
        var tbody = document.getElementById('lf-fin-tbody');
        if (!tbody) return;
        var rec = 0, desp = 0;
        tbody.querySelectorAll('tr').forEach(function(tr) {
            var tipo = (tr.querySelector('[data-field="tipo"]') || {}).value || 'receita';
            var status = (tr.querySelector('[data-field="status"]') || {}).value || 'pendente';
            var valor = parseFloat((tr.querySelector('[data-field="valor"]') || {}).value || 0);
            if (status !== 'cancelado') {
                if (tipo === 'receita') rec += valor;
                else desp += valor;
            }
        });

        var fmt = function(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v); };
        var elRec = document.getElementById('lf-fin-total-rec');
        var elDesp = document.getElementById('lf-fin-total-desp');
        var elSaldo = document.getElementById('lf-fin-total-saldo');
        var elSaldoPill = document.getElementById('lf-fin-saldo-pill');
        if (elRec) elRec.textContent = fmt(rec);
        if (elDesp) elDesp.textContent = fmt(desp);
        if (elSaldo) elSaldo.textContent = fmt(rec - desp);
        if (elSaldoPill) {
            elSaldoPill.className = 'px-2 py-1 text-xs rounded-full font-bold ' +
                (rec - desp >= 0 ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700');
        }
    };

    // ===================== VISIBILITY =====================
    function lfFinUpdateVisibility() {
        var tbody = document.getElementById('lf-fin-tbody');
        var emptyState = document.getElementById('lf-fin-empty');
        var tableWrap = document.getElementById('lf-fin-table-wrap');
        if (!tbody) return;
        var count = tbody.querySelectorAll('tr').length;
        if (emptyState) emptyState.style.display = count === 0 ? '' : 'none';
        if (tableWrap) tableWrap.style.display = count > 0 ? '' : 'none';
    }

    // ===================== VALIDATE =====================
    function lfFinValidate() {
        var tbody = document.getElementById('lf-fin-tbody');
        var errorsDiv = document.getElementById('lf-fin-errors');
        var errorList = document.getElementById('lf-fin-error-list');
        var errors = [];
        if (tbody) {
            var rows = tbody.querySelectorAll('tr');
            rows.forEach(function(tr, idx) {
                var nome = (tr.querySelector('[data-field="nome"]') || {}).value || '';
                var valor = (tr.querySelector('[data-field="valor"]') || {}).value || '';
                var venc = (tr.querySelector('[data-field="data_vencimento"]') || {}).value || '';
                if (!nome.trim()) errors.push('Item #' + (idx + 1) + ': Descrição é obrigatória.');
                if (!valor) errors.push('Item #' + (idx + 1) + ': Valor é obrigatório.');
                if (!venc) errors.push('Item #' + (idx + 1) + ': Vencimento é obrigatório.');
            });
        }

        if (errors.length > 0 && errorList && errorsDiv) {
            errorList.innerHTML = errors.map(function(e) { return '<li>' + e + '</li>'; }).join('');
            errorsDiv.style.display = '';
            return false;
        }
        if (errorsDiv) errorsDiv.style.display = 'none';
        return true;
    }

    // ===================== COLLECT DATA =====================
    function lfFinCollectData() {
        var tbody = document.getElementById('lf-fin-tbody');
        var items = [];
        if (!tbody) return items;
        tbody.querySelectorAll('tr').forEach(function(tr) {
            items.push({
                id: (tr.querySelector('[data-field="id"]') || {}).value || null,
                tipo: (tr.querySelector('[data-field="tipo"]') || {}).value || 'receita',
                category: (tr.querySelector('[data-field="category"]') || {}).value || '',
                nome: (tr.querySelector('[data-field="nome"]') || {}).value || '',
                valor: (tr.querySelector('[data-field="valor"]') || {}).value || 0,
                data_vencimento: (tr.querySelector('[data-field="data_vencimento"]') || {}).value || '',
                status: (tr.querySelector('[data-field="status"]') || {}).value || 'pendente',
                payment_method: (tr.querySelector('[data-field="payment_method"]') || {}).value || '',
                payment_date: (tr.querySelector('[data-field="payment_date"]') || {}).value || ''
            });
        });
        return items;
    }

    // ===================== SAVE =====================
    window.lfFinSave = function() {
        if (!lfFinValidate()) return;

        var items = lfFinCollectData();
        var btn = document.getElementById('lf-fin-btn-save');
        var btnText = document.getElementById('lf-fin-save-text');
        if (btn) btn.disabled = true;
        if (btnText) btnText.textContent = 'Salvando...';

        // Read CSRF token fresh at save-time (not at init-time)
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        // Use XMLHttpRequest to bypass Krayin's fetch wrapper
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SAVE_URL, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onload = function() {
            if (btn) btn.disabled = false;
            if (btnText) btnText.textContent = 'Salvar';

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.status === 'success' || data.message) {
                        if (window.addFlash) {
                            window.addFlash({ type: 'success', message: 'Financeiro salvo com sucesso!' });
                        } else {
                            alert('Salvo com sucesso!');
                        }
                        if (data.data) {
                            window.location.reload();
                        }
                    } else {
                        alert('Erro ao salvar.');
                    }
                } catch(e) {
                    alert('Salvo com sucesso!');
                    window.location.reload();
                }
            } else {
                console.error('Save failed:', xhr.status, xhr.responseText);
                alert('Erro ao salvar. Status: ' + xhr.status);
            }
        };

        xhr.onerror = function() {
            if (btn) btn.disabled = false;
            if (btnText) btnText.textContent = 'Salvar';
            console.error('XHR error');
            alert('Erro de conexão ao salvar financeiro.');
        };

        xhr.send(JSON.stringify({ financeiros: items }));
    };
})();
</script>
@endpush