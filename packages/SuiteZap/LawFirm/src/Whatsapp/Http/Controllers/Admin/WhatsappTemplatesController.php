<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class WhatsappTemplatesController extends Controller
{
    /**
     * Grupos de templates com metadados de exibição.
     * A chave 'prefixes' lista os `name` exatos do system.php.
     */
    private const GROUPS = [
        'prazos' => [
            'label'   => 'Prazos',
            'emoji'   => '📅',
            'color'   => '#3b82f6',
            'prefixes'=> ['new_prazo_client', 'prazo_5dias_cliente', 'prazo_vespera_cliente', 'prazo_hoje_cliente'],
        ],
        'agendador_adv' => [
            'label'   => 'Agendador (Advogado)',
            'emoji'   => '👨‍⚖️',
            'color'   => '#8b5cf6',
            'prefixes'=> ['prazo_5dias_advogado', 'prazo_vespera_advogado', 'prazo_hoje_advogado', 'prazo_resumo_diario'],
        ],
        'financeiro' => [
            'label'   => 'Financeiro',
            'emoji'   => '💰',
            'color'   => '#10b981',
            'prefixes'=> ['financial_billing_due_today', 'financial_billing_overdue'],
        ],
        'ged' => [
            'label'   => 'GED / Documentos',
            'emoji'   => '📄',
            'color'   => '#f59e0b',
            'prefixes'=> ['document_request'],
        ],
        'juridico' => [
            'label'   => 'Jurídico / Monitoramento',
            'emoji'   => '⚖️',
            'color'   => '#6366f1',
            'prefixes'=> ['escavador_monitoramento_update'],
        ],
    ];

    public function index()
    {
        // Lê os campos diretamente do arquivo de configuração do pacote
        $systemPath = __DIR__.'/../../../../Config/system.php';
        $systemConfig = file_exists($systemPath) ? require $systemPath : [];
        $targetGroup = collect($systemConfig)->firstWhere('key', 'lawfirm.whatsapp_templates.messages');
        $rawFields = $targetGroup['fields'] ?? [];

        // Lê os valores salvos no banco
        $templates = [];
        foreach ($rawFields as $field) {
            $name = $field['name'];
            $configKey = 'lawfirm.whatsapp_templates.messages.'.$name;
            $saved = core()->getConfigData($configKey);

            $templates[$name] = [
                'name'    => $name,
                'title'   => $field['title'],
                'info'    => $field['info'] ?? '',
                'default' => $field['default'] ?? '',
                'value'   => $saved ?? $field['default'] ?? '',
                'rows'    => $field['rows'] ?? 4,
            ];
        }

        // Agrupa pelos grupos definidos
        $grouped = [];
        $assigned = [];

        foreach (self::GROUPS as $groupKey => $meta) {
            $items = [];
            foreach ($meta['prefixes'] as $prefix) {
                if (isset($templates[$prefix])) {
                    $items[] = $templates[$prefix];
                    $assigned[] = $prefix;
                }
            }
            if (! empty($items)) {
                $grouped[$groupKey] = array_merge($meta, ['templates' => $items]);
            }
        }

        // Qualquer template não mapeado vai para "Outros"
        $others = array_filter($templates, fn ($t) => ! in_array($t['name'], $assigned));
        if (! empty($others)) {
            $grouped['outros'] = [
                'label'    => 'Outros',
                'emoji'    => '💬',
                'color'    => '#6b7280',
                'prefixes' => [],
                'templates'=> array_values($others),
            ];
        }

        $saveRoute = route('admin.lawfirm.whatsapp.templates.save');
        $csrfToken = csrf_token();

        return view('lawfirm::admin.whatsapp.templates', compact('grouped', 'saveRoute', 'csrfToken'));
    }

    public function save(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        $channelId = core()->getCurrentChannelId();

        foreach ($data as $name => $value) {
            $configKey = 'lawfirm.whatsapp_templates.messages.'.$name;

            // Upsert direto na core_config (mesmo mecanismo do Krayin)
            DB::table('core_config')->updateOrInsert(
                [
                    'code'       => $configKey,
                    'channel_id' => $channelId,
                    'locale_id'  => null,
                ],
                [
                    'value'      => $value,
                ]
            );
        }

        // Limpa o cache do core para que core()->getConfigData() reflita as mudanças
        cache()->forget('core_config_'.$channelId);

        return response()->json([
            'success' => true,
            'message' => 'Templates salvos com sucesso!',
        ]);
    }
}
