<?php

namespace SuiteZap\LawFirm\Whatsapp\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Whatsapp\Models\MothershipWhatsappTemplate;

/**
 * WA-TPL-001 — Controller de Templates WhatsApp.
 *
 * Hierarquia de resolução do texto de cada template:
 *  1. Sobrescrita local do tenant  → core_config (salva pelo próprio escritório)
 *  2. Padrão global do MotherShip  → lawfirm_whatsapp_templates (via MothershipWhatsappTemplate)
 *  3. Fallback hardcoded           → system.php (retrocompatibilidade, caso o MotherShip não esteja acessível)
 */
class WhatsappTemplatesController extends Controller
{
    /**
     * Grupos de templates com metadados de exibição.
     * A chave 'prefixes' lista os `name` exatos usados como chave na tabela.
     */
    private const GROUPS = [
        'prazos' => [
            'label'    => 'Prazos',
            'emoji'    => '📅',
            'color'    => '#3b82f6',
            'prefixes' => ['new_prazo_client', 'prazo_5dias_cliente', 'prazo_vespera_cliente', 'prazo_hoje_cliente'],
        ],
        'agendador_adv' => [
            'label'    => 'Agendador (Advogado)',
            'emoji'    => '👨‍⚖️',
            'color'    => '#8b5cf6',
            'prefixes' => ['prazo_5dias_advogado', 'prazo_vespera_advogado', 'prazo_hoje_advogado', 'prazo_resumo_diario'],
        ],
        'financeiro' => [
            'label'    => 'Financeiro',
            'emoji'    => '💰',
            'color'    => '#10b981',
            'prefixes' => ['financial_billing_due_today', 'financial_billing_overdue'],
        ],
        'ged' => [
            'label'    => 'GED / Documentos',
            'emoji'    => '📄',
            'color'    => '#f59e0b',
            'prefixes' => ['document_request', 'registration_request'],
        ],
        'juridico' => [
            'label'    => 'Jurídico / Monitoramento',
            'emoji'    => '⚖️',
            'color'    => '#6366f1',
            'prefixes' => ['escavador_monitoramento_update'],
        ],
    ];

    public function index()
    {
        // --- 1. Carrega metadados do system.php (fallback hardcoded de última instância) ---
        $systemPath = __DIR__.'/../../../../Config/system.php';
        $systemConfig = file_exists($systemPath) ? require $systemPath : [];
        $targetGroup = collect($systemConfig)->firstWhere('key', 'lawfirm.whatsapp_templates.messages');
        $rawFields = collect($targetGroup['fields'] ?? [])->keyBy('name');

        // --- 2. Tenta carregar templates globais do MotherShip ---
        $mothershipTemplates = collect();
        try {
            $mothershipTemplates = MothershipWhatsappTemplate::activeIndexedByName();
        } catch (\Throwable $e) {
            // MotherShip inacessível: degrada graciosamente para o fallback do system.php
            Log::error('[WA-TPL-001] Falha ao carregar templates do MotherShip, usando fallback local.', [
                'error' => $e->getMessage(),
            ]);
        }

        // --- 3. Unifica a lista de chaves (MotherShip + system.php, sem duplicatas) ---
        $allKeys = $mothershipTemplates->keys()
            ->merge($rawFields->keys())
            ->unique()
            ->values();

        // --- 4. Monta a lista de templates resolvendo a hierarquia por chave ---
        $templates = [];
        foreach ($allKeys as $name) {
            $configKey = 'lawfirm.whatsapp_templates.messages.'.$name;
            $localValue = core()->getConfigData($configKey); // sobrescrita do tenant

            $msTemplate = $mothershipTemplates->get($name);
            $sysField = $rawFields->get($name);

            // Hierarquia de texto padrão: MotherShip → system.php
            $globalDefault = $msTemplate?->default_text ?? $sysField['default'] ?? '';

            // Hierarquia de metadados: MotherShip → system.php → valores sintéticos
            $title = $msTemplate?->title ?? $sysField['title'] ?? $name;
            $info = $msTemplate?->info ?? $sysField['info'] ?? '';
            $rows = $msTemplate?->rows ?? $sysField['rows'] ?? 4;

            $templates[$name] = [
                'name'             => $name,
                'title'            => $title,
                'info'             => $info,
                'default'          => $globalDefault,
                'value'            => $localValue ?? $globalDefault,
                'rows'             => $rows,
                'from_mothership'  => $msTemplate !== null, // flag informativa para a view
            ];
        }

        // --- 5. Agrupa pelos grupos canônicos ---
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

        // Templates do MotherShip que ainda não pertencem a nenhum grupo canônico
        // são agrupados dinamicamente pelo campo `group` da tabela
        foreach ($templates as $name => $tpl) {
            if (in_array($name, $assigned, true)) {
                continue;
            }

            $msGroup = $mothershipTemplates->get($name)?->group ?? 'outros';

            if (! isset($grouped[$msGroup])) {
                $grouped[$msGroup] = [
                    'label'     => ucfirst($msGroup),
                    'emoji'     => '💬',
                    'color'     => '#6b7280',
                    'prefixes'  => [],
                    'templates' => [],
                ];
            }

            $grouped[$msGroup]['templates'][] = $tpl;
            $assigned[] = $name;
        }

        // Qualquer sobra vai para "Outros"
        $others = array_filter($templates, fn ($t) => ! in_array($t['name'], $assigned, true));
        if (! empty($others)) {
            $grouped['outros'] = [
                'label'     => 'Outros',
                'emoji'     => '💬',
                'color'     => '#6b7280',
                'prefixes'  => [],
                'templates' => array_values($others),
            ];
        }

        $saveRoute = route('admin.lawfirm.whatsapp.templates.save');
        $csrfToken = csrf_token();

        // Global JS Map (SKILL.md §6 — Global JS Map Injection)
        // Indexado por `name` para lookup O(1) no JS sem passar JSON em onclick inline.
        $tplMap = [];
        foreach ($templates as $name => $tpl) {
            preg_match_all('/\{([a-z_]+)\}/', ($tpl['info'] ?? '').' '.($tpl['default'] ?? ''), $varMatches);
            $tplMap[$name] = [
                'name'    => $tpl['name'],
                'title'   => $tpl['title'],
                'value'   => $tpl['value'],
                'default' => $tpl['default'],
                'info'    => $tpl['info'] ?? '',
                'rows'    => $tpl['rows'] ?? 4,
                'vars'    => array_unique($varMatches[0]),
            ];
        }

        return view('lawfirm::admin.whatsapp.templates', compact('grouped', 'saveRoute', 'csrfToken', 'tplMap'));
    }

    public function save(Request $request)
    {
        $data = $request->except(['_token', '_method']);

        $channelId = core()->getCurrentChannelId();

        foreach ($data as $name => $value) {
            $configKey = 'lawfirm.whatsapp_templates.messages.'.$name;

            DB::table('core_config')->updateOrInsert(
                [
                    'code'       => $configKey,
                    'channel_id' => $channelId,
                    'locale_id'  => null,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        // Limpa cache para que core()->getConfigData() reflita as mudanças
        cache()->forget('core_config_'.$channelId);

        return response()->json([
            'success' => true,
            'message' => 'Templates salvos com sucesso!',
        ]);
    }
}
