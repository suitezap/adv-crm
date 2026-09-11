{{-- Modal de Importação do WhatsApp --}}
<div id="whatsapp-import-modal" class="relative z-[100]" aria-labelledby="wa-import-modal-title" role="dialog" aria-modal="true" style="display: none;">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal Panel -->
            <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl dark:bg-gray-900" style="max-width:60vw;">
                
                <!-- Header -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center dark:bg-gray-800 border-b dark:border-gray-700">
                    <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="wa-import-modal-title">
                        ☁️ Importar Histórico WhatsApp
                    </h3>
                    <button onclick="window.lfCloseWhatsappImportModal()" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none dark:hover:text-gray-300">
                        <span class="sr-only">Fechar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @php
                    $personPhones = $processo->person ? $processo->person->contact_numbers : [];
                    $prefillPhone = is_array($personPhones) && count($personPhones) > 0 ? current($personPhones)['value'] : '';
                @endphp

                <!-- Body -->
                <div class="px-4 py-5 sm:p-6 bg-white dark:bg-gray-900">
                    <div class="space-y-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            O histórico de mensagens serão importadas do WhatsApp e vinculadas a este Processo. Essa tarefa será realizada em segundo plano. Por favor aguarde notificação ao final deste procedimento.
                        </p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 required mb-1">Número do WhatsApp (Cliente)</label>
                            <input type="text" id="wa-import-remotejid" class="control-input mt-1 block w-full border border-gray-300 rounded px-3 py-2 dark:border-gray-600 dark:bg-gray-800" placeholder="5511999999999" value="{{ $prefillPhone }}" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">A partir de (Opcional)</label>
                                <input type="date" id="wa-import-start" class="control-input mt-1 block w-full border border-gray-300 rounded px-3 py-2 dark:border-gray-600 dark:bg-gray-800">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Até (Opcional)</label>
                                <input type="date" id="wa-import-end" class="control-input mt-1 block w-full border border-gray-300 rounded px-3 py-2 dark:border-gray-600 dark:bg-gray-800">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-4 py-4 sm:px-6 dark:bg-gray-800 border-t dark:border-gray-700 flex justify-center items-center gap-4">
                    <button type="button" onclick="window.lfCloseWhatsappImportModal()" class="secondary-button px-6">
                        Cancelar
                    </button>
                    <button type="button" id="wa-import-submit-btn" onclick="window.lfSubmitWhatsappImport()" class="primary-button px-6 disabled:opacity-50 disabled:cursor-not-allowed">
                        Iniciar Importação
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    window.lfOpenWhatsappImportModal = function() {
        const modal = document.getElementById('whatsapp-import-modal');
        if (modal && modal.parentNode !== document.body) {
            document.body.appendChild(modal);
        }
        modal.style.display = 'block';
    };

    window.lfCloseWhatsappImportModal = function() {
        document.getElementById('whatsapp-import-modal').style.display = 'none';
        // Reset state
        const btn = document.getElementById('wa-import-submit-btn');
        btn.disabled = false;
        btn.innerText = 'Iniciar Importação';
    };

    window.lfSubmitWhatsappImport = async function() {
        const btn = document.getElementById('wa-import-submit-btn');
        const phone = document.getElementById('wa-import-remotejid').value;
        const start = document.getElementById('wa-import-start').value;
        const end = document.getElementById('wa-import-end').value;

        if (!phone) {
            alert('Por favor, informe o número do WhatsApp.');
            return;
        }

        btn.disabled = true;
        btn.innerText = 'Agendando...';

        const url = "{{ route('admin.lawfirm.whatsapp.import', 'REPLACE_ID') }}".replace('REPLACE_ID', '{{ $processo->id }}');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        try {
            const formData = new FormData();
            formData.append('remote_jid', phone);
            if (start) formData.append('start_date', start);
            if (end) formData.append('end_date', end);

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            let data;
            try {
                data = await response.json();
            } catch (jsonError) {
                throw new Error('O servidor retornou um erro não esperado (não é JSON). Resposta HTTP: ' + response.status);
            }

            if (data.success) {
                alert(data.message);
                window.lfCloseWhatsappImportModal();
            } else {
                alert(data.error || 'Erro ao agendar importação.');
            }
        } catch (error) {
            console.error(error);
            alert('Ocorreu um erro na requisição: ' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerText = 'Iniciar Importação';
        }
    };
</script>
@endpush
