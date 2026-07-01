<?php

namespace SuiteZap\LawFirm\Whatsapp\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Legal\Models\Prazo;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use SuiteZap\LawFirm\Whatsapp\Services\EvolutionService;

class SendScheduledPrazoNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lawfirm:prazo-notifications
                            {--dry-run : Lista os prazos que seriam notificados sem enviar mensagens}';

    /**
     * The console command description.
     */
    protected $description = 'Robô Agendador: Envia notificações WhatsApp de prazos jurídicos a clientes e advogados';

    protected EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        parent::__construct();
        $this->evolutionService = $evolutionService;
    }

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $hoje = Carbon::today();

        $this->info('🤖 Robô Agendador — '.$hoje->format('d/m/Y'));

        // ─── Verificação de Conexão WhatsApp ────────────────────────────────
        $config = MotherShipService::getEvolutionConfig();

        if (! $config || empty($config['instance'])) {
            $this->error('WhatsApp não configurado no MotherShip para este Tenant. Abortando.');
            Log::warning('lawfirm:prazo-notifications: Evolution API não configurada. Abortando.');

            return self::FAILURE;
        }

        $instanceName = $config['instance'];

        if (! $dryRun) {
            $status = $this->evolutionService->fetchInstance($instanceName);
            $connected = isset($status['data'][0]['connectionStatus']) &&
                         $status['data'][0]['connectionStatus'] === 'open';

            if (! $connected) {
                $this->warn("WhatsApp ({$instanceName}) não está conectado. Abortando envios.");
                Log::warning("lawfirm:prazo-notifications: Instância '{$instanceName}' desconectada. Abortando.");

                return self::FAILURE;
            }

            $this->info("✅ WhatsApp conectado: {$instanceName}");
        } else {
            $this->warn('[DRY-RUN] Verificação de conexão ignorada.');
        }

        // ─── Busca Prazos com notificação habilitada ─────────────────────────
        $prazos = Prazo::with(['processo.person', 'processo.responsavel'])
            ->where('notificar_whatsapp', true)
            ->where('status', 'pendente')
            ->whereNotNull('data_vencimento')
            ->get();

        $this->info("📋 Prazos com notificação ativa: {$prazos->count()}");

        $resumoDiario = []; // Agrupado por advogado: ['nome' => ..., 'whatsapp' => ..., 'linhas' => [...]]

        foreach ($prazos as $prazo) {
            $processo = $prazo->processo;
            if (! $processo) {
                continue;
            }

            $vencimento = Carbon::parse($prazo->data_vencimento)->startOfDay();
            $diasRestantes = $hoje->diffInDays($vencimento, false); // negativo se atrasado

            $this->line("  → Prazo #{$prazo->id} | {$prazo->titulo} | Vence em {$diasRestantes} dias");

            // ─── Coleta dados para o resumo diário ──────────────────────────
            if ($diasRestantes === 0) {
                $advogado = $processo->responsavel;
                if ($advogado && ! empty($advogado->whatsapp)) {
                    $chave = $advogado->whatsapp;
                    if (! isset($resumoDiario[$chave])) {
                        $resumoDiario[$chave] = [
                            'nome'   => $advogado->name ?? 'Advogado(a)',
                            'linhas' => [],
                        ];
                    }
                    $cnj = $processo->numero_cnj ?? '—';
                    $resumoDiario[$chave]['linhas'][] = "📌.{$cnj} — {$processo->titulo} (Cliente: ".($processo->person->name ?? '?').')';
                }
            }

            // ─── Envios por Janela Temporal ──────────────────────────────────
            if ($diasRestantes === 5 && is_null($prazo->ultima_notificacao_5d)) {
                $this->sendNotification($prazo, 'prazo_5dias', $dryRun, $instanceName);
                if (! $dryRun) {
                    $prazo->update(['ultima_notificacao_5d' => Carbon::now()]);
                }
            }

            if ($diasRestantes === 1 && is_null($prazo->ultima_notificacao_1d)) {
                $this->sendNotification($prazo, 'prazo_vespera', $dryRun, $instanceName);
                if (! $dryRun) {
                    $prazo->update(['ultima_notificacao_1d' => Carbon::now()]);
                }
            }

            if ($diasRestantes === 0 && is_null($prazo->ultima_notificacao_0d)) {
                $this->sendNotification($prazo, 'prazo_hoje', $dryRun, $instanceName);
                if (! $dryRun) {
                    $prazo->update(['ultima_notificacao_0d' => Carbon::now()]);
                }
            }
        }

        // ─── Resumo Diário por Advogado ──────────────────────────────────────
        if (! empty($resumoDiario)) {
            $this->info('📨 Enviando resumo diário para '.count($resumoDiario).' advogado(s)...');
            $templateResumo = core()->getConfigData('lawfirm.whatsapp_templates.messages.prazo_resumo_diario')
                ?: "📋 *Resumo — {data_hoje}*\n\nBom dia, Dr(a). {advogado_nome}! Seus compromissos de hoje:\n\n{lista_compromissos}\n\nTenha um excelente dia!";

            foreach ($resumoDiario as $phone => $data) {
                $lista = implode("\n", $data['linhas']);
                $msg = str_replace(
                    ['{advogado_nome}', '{data_hoje}', '{lista_compromissos}'],
                    [$data['nome'], $hoje->format('d/m/Y'), $lista],
                    $templateResumo
                );

                $cleanPhone = $this->normalizePhone($phone);
                if ($dryRun) {
                    $this->warn("[DRY-RUN] Resumo para {$cleanPhone} (Dr. {$data['nome']}):\n{$msg}");
                } else {
                    $result = $this->evolutionService->sendMessage($instanceName, $cleanPhone, $msg);
                    Log::info("lawfirm:prazo-notifications: Resumo enviado para {$cleanPhone}. Resultado: ".json_encode($result));
                    $this->info("  ✅ Resumo enviado para {$cleanPhone} (Dr. {$data['nome']})");
                }
            }
        }

        $this->info('🏁 Robô Agendador concluído.');

        return self::SUCCESS;
    }

    /**
     * Envia notificação para cliente e advogado de um prazo numa janela temporal.
     * Janelas: 'prazo_5dias', 'prazo_vespera', 'prazo_hoje'
     */
    private function sendNotification(Prazo $prazo, string $janela, bool $dryRun, string $instanceName): void
    {
        $processo = $prazo->processo;
        $person = $processo->person ?? null;
        $advogado = $processo->responsavel ?? null;

        $prazoData = Carbon::parse($prazo->data_vencimento)->format('d/m/Y');
        $cnj = $processo->numero_cnj ?? '—';

        // ── Notificação para o CLIENTE ───────────────────────────────────────
        if ($person) {
            $phone = $this->getPersonPhone($person);
            if ($phone) {
                $template = core()->getConfigData("lawfirm.whatsapp_templates.messages.{$janela}_cliente");
                if ($template) {
                    $msg = str_replace(
                        ['{cliente_nome}', '{prazo_titulo}', '{prazo_data}', '{processo_cnj}', '{processo_titulo}'],
                        [$person->name, $prazo->titulo, $prazoData, $cnj, $processo->titulo],
                        $template
                    );
                    if ($dryRun) {
                        $this->warn("[DRY-RUN] Cliente {$person->name} ({$phone}):\n{$msg}");
                    } else {
                        $result = $this->evolutionService->sendMessage($instanceName, $phone, $msg);
                        Log::info("lawfirm:prazo-notifications [{$janela}_cliente]: Prazo #{$prazo->id} → {$phone}. Resultado: ".json_encode($result));
                        $this->info("    📤 [{$janela}_cliente] → {$person->name} ({$phone})");
                    }
                } else {
                    $this->warn("    ⚠️  Template '{$janela}_cliente' não configurado. Pulando envio ao cliente.");
                }
            }
        }

        // ── Notificação para o ADVOGADO RESPONSÁVEL ──────────────────────────
        if ($advogado && ! empty($advogado->whatsapp)) {
            $phone = $this->normalizePhone($advogado->whatsapp);
            $template = core()->getConfigData("lawfirm.whatsapp_templates.messages.{$janela}_advogado");
            if ($template) {
                $msg = str_replace(
                    ['{advogado_nome}', '{prazo_titulo}', '{prazo_data}', '{processo_cnj}', '{processo_titulo}', '{cliente_nome}'],
                    [$advogado->name, $prazo->titulo, $prazoData, $cnj, $processo->titulo, $person->name ?? '—'],
                    $template
                );
                if ($dryRun) {
                    $this->warn("[DRY-RUN] Advogado {$advogado->name} ({$phone}):\n{$msg}");
                } else {
                    $result = $this->evolutionService->sendMessage($instanceName, $phone, $msg);
                    Log::info("lawfirm:prazo-notifications [{$janela}_advogado]: Prazo #{$prazo->id} → {$phone}. Resultado: ".json_encode($result));
                    $this->info("    📤 [{$janela}_advogado] → {$advogado->name} ({$phone})");
                }
            } else {
                $this->warn("    ⚠️  Template '{$janela}_advogado' não configurado. Pulando envio ao advogado.");
            }
        }
    }

    /**
     * Extrai e normaliza o primeiro telefone de um Person do Krayin.
     */
    private function getPersonPhone($person): ?string
    {
        $contacts = $person->contact_numbers;
        if (! $contacts) {
            return null;
        }

        $phone = null;
        if (is_array($contacts)) {
            $phone = $contacts[0]['value'] ?? null;
        } elseif ($contacts instanceof \Illuminate\Support\Collection) {
            $first = $contacts->first();
            $phone = $first ? (is_object($first) ? $first->value : $first['value'] ?? null) : null;
        }

        return $phone ? $this->normalizePhone($phone) : null;
    }

    /**
     * Remove caracteres não numéricos e adiciona DDI 55 se necessário.
     */
    private function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($clean) <= 11) {
            $clean = '55'.$clean;
        }

        return $clean;
    }
}
