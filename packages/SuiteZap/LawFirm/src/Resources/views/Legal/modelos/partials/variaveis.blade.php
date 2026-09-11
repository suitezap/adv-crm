{{--
    Partial: variaveis.blade.php
    Exibe chips clicáveis com todas as variáveis suportadas nos modelos de documento.
    Ao clicar em um chip, a variável correspondente é inserida na posição do cursor no #conteudo-editor.
--}}
<div class="rounded-xl bg-blue-50 border border-blue-100 dark:bg-blue-900/20 dark:border-blue-800/60 p-4 space-y-3">
    <div class="flex items-center justify-between">
        <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 flex items-center gap-1.5">
            <i class="icon-code text-blue-500"></i>
            Variáveis Suportadas
        </h4>
        <span class="text-xs text-blue-500 dark:text-blue-400">Clique para inserir no conteúdo</span>
    </div>

    @php
        $grupos = [
            '👤 Cliente' => [
                'cliente_nome'         => 'Nome',
                'cliente_cpf'          => 'CPF',
                'cliente_rg'           => 'RG',
                'cliente_email'        => 'E-mail',
                'cliente_telefone'     => 'Telefone',
                'cliente_estado_civil' => 'Estado Civil',
                'cliente_nacionalidade'=> 'Nacionalidade',
                'cliente_profissao'    => 'Profissão',
                'cliente_cep'          => 'CEP',
                'cliente_logradouro'   => 'Logradouro (Rua/Avenida)',
                'cliente_numero'       => 'Número',
                'cliente_complemento'  => 'Complemento',
                'cliente_bairro'       => 'Bairro',
                'cliente_cidade'       => 'Cidade',
                'cliente_uf'           => 'Estado (UF)',
            ],
            '⚖️ Advogado / Responsável' => [
                'advogado_nome'      => 'Nome',
                'advogado_oab'       => 'OAB',
                'advogado_whatsapp'  => 'WhatsApp',
            ],
            '🏢 Empresa (Organização)' => [
                'empresa_nome' => 'Nome',
                'empresa_cnpj' => 'CNPJ',
            ],
            '📂 Processo' => [
                'processo_titulo'      => 'Título',
                'processo_numero_cnj'  => 'Nº CNJ',
                'processo_area'        => 'Área',
                'processo_tribunal'    => 'Tribunal',
                'processo_vara'        => 'Vara',
                'processo_comarca'     => 'Comarca',
                'processo_valor_causa' => 'Valor da Causa',
                'parte_contraria'      => 'Parte Contrária',
            ],
            '📋 Escritório' => [
                'escritorio_nome'        => 'Nome do Escritório',
                'escritorio_whatsapp'    => 'WhatsApp / Contato Principal',
                'escritorio_email'       => 'E-mail Profissional',
                'escritorio_cep'         => 'CEP',
                'escritorio_logradouro'  => 'Logradouro (Rua/Avenida)',
                'escritorio_numero'      => 'Número',
                'escritorio_complemento' => 'Complemento',
                'escritorio_bairro'      => 'Bairro',
                'escritorio_cidade'      => 'Cidade',
                'escritorio_uf'          => 'Estado (UF)',
            ],
            '📅 Outros' => [
                'data_hoje' => 'Data de Hoje',
            ],
        ];
    @endphp

    <div class="space-y-4">
        @foreach($grupos as $grupo => $vars)
            <div class="flex flex-col md:flex-row md:items-start gap-2 border-b border-blue-100/50 dark:border-blue-950/20 pb-3 last:border-0 last:pb-0">
                <span class="text-sm font-semibold text-blue-800 dark:text-blue-300 md:w-56 shrink-0 md:pt-2 flex items-center">
                    {{ $grupo }}
                </span>
                <div class="flex flex-wrap gap-2">
                    @foreach($vars as $var => $label)
                        <button type="button"
                            onclick="lfInsertVariable('{{ $var }}')"
                            title="Clique para inserir &#123;&#123;{{ $var }}&#125;&#125; ({{ $label }})"
                            class="group inline-flex flex-col items-start rounded-lg border border-blue-200 bg-white px-3 py-1.5 shadow-sm hover:bg-blue-600 hover:border-blue-600 active:scale-95 transition-all duration-100 cursor-pointer dark:bg-blue-900/30 dark:border-blue-700 select-none text-left">
                            <span class="font-mono text-[11px] text-blue-700 dark:text-blue-300 group-hover:text-white transition-colors duration-100">{{ '{' . '{' . $var . '}' . '}' }}</span>
                            <span class="text-[10px] text-gray-400 dark:text-gray-400 group-hover:text-blue-100 transition-colors duration-100 mt-0.5">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

@once
<script>
    /**
     * Insere a variável na posição atual do cursor no textarea #conteudo-editor.
     * Tenta usar a API do TinyMCE caso o editor esteja ativo, com fallback para o textarea nativo.
     */
    function lfInsertVariable(varName) {
        const tag = '{' + '{' + varName + '}' + '}';

        // 1. Tenta inserir via TinyMCE se estiver carregado e ativo
        if (window.tinymce && window.tinymce.get('conteudo-editor')) {
            const editor = window.tinymce.get('conteudo-editor');
            editor.focus();
            editor.execCommand('mceInsertContent', false, tag);
            return;
        }

        // 2. Fallback para o textarea padrão caso o TinyMCE não esteja ativo/carregado
        const textarea = document.querySelector('textarea[name="conteudo"]');
        if (!textarea) {
            console.warn('[LF] Textarea[name=conteudo] não encontrado.');
            return;
        }

        const start = textarea.selectionStart ?? textarea.value.length;
        const end   = textarea.selectionEnd   ?? textarea.value.length;

        const before = textarea.value.substring(0, start);
        const after  = textarea.value.substring(end);

        textarea.value = before + tag + after;

        // Reposiciona o cursor após a variável inserida
        const newPos = start + tag.length;
        textarea.selectionStart = newPos;
        textarea.selectionEnd   = newPos;
        textarea.focus();

        // Dispara evento input para frameworks que escutam mudanças
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }
</script>
@endonce
