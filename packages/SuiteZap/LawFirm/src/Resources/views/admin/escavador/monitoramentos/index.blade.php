<x-admin::layouts>
    <x-slot:title>
        Monitoramentos Ativos no Escavador
    </x-slot>

    <div class="flex flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            <div class="flex flex-col gap-2">
                <div class="text-xl font-bold dark:text-white">
                    Seus Monitoramentos (Robôs do Assistente Jurídico)
                </div>
                <div class="text-sm text-gray-500">
                    Acompanhamento ativo de Termos, Processos ou Diários Oficiais e integração com WhatsApp.
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('lawfirm.escavador.index') }}" class="text-blue-600 hover:underline text-sm font-semibold">
                    ← Voltar ao Início
                </a>
            </div>
        </div>

        <div class="page-content">
            <x-admin::datagrid :src="route('lawfirm.escavador.monitoramentos.index')" />
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleWhatsappNotification(id, checkbox, url) {
                // Disable the checkbox to prevent multiple clicks while processing
                checkbox.disabled = true;

                // Fire AJAX request using Fetch API
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    checkbox.disabled = false;
                    if (data.status === 'success') {
                        // Create a simple toast notification
                        window.addFlash({
                            type: 'success',
                            message: data.message
                        });
                        
                        // Ensure the checkbox matches the server state
                        checkbox.checked = data.notify_whatsapp;
                    } else {
                        // Revert on custom error
                        checkbox.checked = !checkbox.checked;
                        window.addFlash({
                            type: 'error',
                            message: data.message || 'Erro ao atualizar a configuração.'
                        });
                    }
                })
                .catch(error => {
                    checkbox.disabled = false;
                    checkbox.checked = !checkbox.checked; // Revert change
                    console.error('Error toggling WhatsApp notification:', error);
                    window.addFlash({
                        type: 'error',
                        message: 'Erro na requisição. Tente novamente mais tarde.'
                    });
                });
            }
        </script>
    @endpush

            <style>
            .lf-esc-overlay {
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
            }
            .lf-esc-dialog {
                position: fixed;
                top: 50%; left: 50%;
                transform: translate(-50%, -50%);
                background: #fff;
                border-radius: 12px;
                z-index: 1001;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            }
            .dark .lf-esc-dialog {
                background: #1f2937;
                color: #e5e7eb;
            }
            .lf-esc-modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 16px 24px;
            }
            .dark .lf-esc-modal-header {
                background: #374151;
                border-bottom: 1px solid #4b5563;
            }
            .lf-esc-close-btn {
                background: none;
                border: none;
                font-size: 1.25rem;
                cursor: pointer;
                color: #9ca3af;
                padding: 4px;
            }
            .lf-esc-close-btn:hover {
                color: #4b5563;
            }
            .lf-esc-modal-title {
                font-size: 1.25rem;
                font-weight: 700;
                margin: 0;
            }
            .dark .lf-esc-modal-title {
                color: #f9fafb;
            }
            .lf-esc-btn {
                background: #0d9488;
                color: #fff;
                border: none;
                padding: 10px 20px;
                border-radius: 8px;
                font-weight: 600;
                cursor: pointer;
                transition: background 0.2s;
            }
            .lf-esc-btn:hover {
                background: #0f766e;
            }
            .lf-esc-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }
            .lf-esc-input, .lf-esc-select {
                border: 1px solid #d1d5db;
                border-radius: 8px;
                padding: 10px 14px;
                width: 100%;
                font-size: 0.95rem;
                transition: border-color 0.2s;
            }
            .lf-esc-input:focus, .lf-esc-select:focus {
                outline: none;
                border-color: #0d9488;
                box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            }
            .dark .lf-esc-input, .dark .lf-esc-select {
                background: #374151;
                border-color: #4b5563;
                color: #e5e7eb;
            }
            .dark .lf-esc-input:focus, .dark .lf-esc-select:focus {
                border-color: #14b8a6;
            }
        </style>

    <!-- Switch style -->
    @push('styles')
        <style>
            /* The switch - the box around the slider */
            .switch {
                position: relative;
                display: inline-block;
                width: 44px;
                height: 24px;
                margin-top: 5px;
            }

            /* Hide default HTML checkbox */
            .switch input {
                opacity: 0;
                width: 0;
                height: 0;
            }

            /* The slider */
            .slider {
                position: absolute;
                cursor: pointer;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: #ccc;
                -webkit-transition: .4s;
                transition: .4s;
            }

            .slider:before {
                position: absolute;
                content: "";
                height: 18px;
                width: 18px;
                left: 3px;
                bottom: 3px;
                background-color: white;
                -webkit-transition: .4s;
                transition: .4s;
            }

            input:checked + .slider {
                background-color: #2196F3;
            }

            input:focus + .slider {
                box-shadow: 0 0 1px #2196F3;
            }

            input:checked + .slider:before {
                -webkit-transform: translateX(20px);
                -ms-transform: translateX(20px);
                transform: translateX(20px);
            }

            /* Rounded sliders */
            .slider.round {
                border-radius: 34px;
            }

            .slider.round:before {
                border-radius: 50%;
            }

            /* Dark mode for switch border/background if needed */
            .dark .slider {
                background-color: #4b5563; /* tailwind gray-600 */
            }
            .dark input:checked + .slider {
                background-color: #3b82f6; /* tailwind blue-500 */
            }
        </style>
    @endpush
</x-admin::layouts>
