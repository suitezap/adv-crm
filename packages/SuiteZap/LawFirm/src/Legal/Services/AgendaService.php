<?php

namespace SuiteZap\LawFirm\Legal\Services;

use Carbon\Carbon;
use SuiteZap\LawFirm\Legal\Models\Prazo;
use Webkul\Activity\Models\Activity;

class AgendaService
{
    /**
     * Retorna todos os eventos unificados (Atividades + Prazos) formatados para FullCalendar.
     */
    public function getEventosUnificados(): array
    {
        $userId = auth()->id();
        $eventos = [];

        // -------------------------------------------------------
        // 1. Atividades do Krayin (Reuniões, Audiências, Ligações, etc.)
        //    Excluímos apenas as atividades-clone geradas pelo PrazoObserver
        //    (tipo 'call' vinculadas a um prazo). As Audiências geradas pelo
        //    ProcessoObserver (comentário com [REF:PROC_ID:]) devem aparecer pois
        //    são os compromissos com horário específico — ponto central da agenda.
        // -------------------------------------------------------
        $atividades = Activity::where('user_id', $userId)
            ->with(['participants.user', 'participants.person'])
            ->whereNotIn('id', function ($query) {
                $query->select('activity_id')
                    ->from('law_processo_prazos')
                    ->whereNotNull('activity_id');
            })
            ->get();

        foreach ($atividades as $atividade) {
            $isDone = $atividade->is_done;
            $isAudiencia = str_contains($atividade->comment ?? '', '[REF:PROC_ID:');

            if ($isAudiencia) {
                // Audiência gerada automaticamente pelo processo — destaque em roxo
                $bg = $isDone ? '#f5f3ff' : '#ede9fe'; // violet-50 / violet-100
                $border = $isDone ? '#ddd6fe' : '#c4b5fd'; // violet-200 / violet-300
                $txtClr = $isDone ? '#6b7280' : '#5b21b6'; // gray-500  / violet-800
                $emoji = '🏛️';
            } else {
                $bg = $isDone ? '#f3f4f6' : '#eff6ff'; // gray-100 / blue-50
                $border = $isDone ? '#e5e7eb' : '#bfdbfe'; // gray-200 / blue-200
                $txtClr = $isDone ? '#6b7280' : '#1e3a8a'; // gray-500 / blue-900
                $emoji = $this->getActivityEmoji($atividade->type);
            }

            $participantsList = [];
            foreach ($atividade->participants as $participant) {
                if ($participant->user) {
                    $participantsList[] = ['id' => 'u'.$participant->user->id, 'name' => $participant->user->name, 'type' => 'user'];
                } elseif ($participant->person) {
                    $participantsList[] = ['id' => 'p'.$participant->person->id, 'name' => $participant->person->name, 'type' => 'person'];
                }
            }

            $eventos[] = [
                'id'              => 'act_'.$atividade->id,
                'title'           => $emoji.' '.$atividade->title,
                'start'           => $atividade->schedule_from?->toIso8601String(),
                'end'             => $atividade->schedule_to?->toIso8601String(),
                'backgroundColor' => $bg,
                'borderColor'     => $border,
                'textColor'       => $txtClr,
                'allDay'          => false,
                'extendedProps'   => [
                    'type'         => 'activity',
                    'id'           => $atividade->id,
                    'isAudiencia'  => $isAudiencia,
                    'activityType' => $atividade->type,
                    'comment'      => strip_tags($atividade->comment ?? ''),
                    'isDone'       => $isDone,
                    'participants' => $participantsList,
                ],
            ];
        }

        // -------------------------------------------------------
        // 2. Busca Prazos do LawFirm (vinculados a Processos)
        // -------------------------------------------------------
        $prazos = Prazo::whereHas('processo')
            ->with('processo:id,titulo')
            ->get();

        foreach ($prazos as $prazo) {
            // Ignora duplicata visual: O ProcessoObserver já cria a Atividade da Audiência com hora exata.
            // O DeadlineService cria um Prazo automático correspondente. Nós omitimos esse Prazo da Agenda
            // para dar precedência à Atividade que contém o horário e informações exatas.
            if ($prazo->descricao === 'Prazo criado automaticamente a partir da Data da Audiência.') {
                continue;
            }

            $isConcluido = in_array(strtolower($prazo->status), ['concluído', 'concluido']);

            // Pastel Green for done, Pastel Red for pending
            $bg = $isConcluido ? '#f0fdf4' : '#fef2f2'; // green-50 : red-50
            $border = $isConcluido ? '#bbf7d0' : '#fecaca'; // green-200 : red-200
            $text = $isConcluido ? '#166534' : '#991b1b'; // green-800 : red-800

            $icone = strtolower(trim($prazo->tipo)) === 'tarefa' ? '📋' : '⚖️';

            $eventos[] = [
                'id'              => 'prz_'.$prazo->id,
                'title'           => $icone.' '.$prazo->titulo,
                'start'           => $prazo->data_vencimento?->toIso8601String(),
                'allDay'          => false,
                'backgroundColor' => $bg,
                'borderColor'     => $border,
                'textColor'       => $text,
                'extendedProps'   => [
                    'type'        => 'prazo',
                    'id'          => $prazo->id,
                    'processo'    => $prazo->processo?->titulo ?? '',
                    'processo_id' => $prazo->processo_id,
                    'comment'     => strip_tags($prazo->descricao ?? ''),
                    'isDone'      => $isConcluido,
                    'status'      => $prazo->status,
                ],
            ];
        }

        return $eventos;
    }

    /**
     * Atualiza a data de um evento após drag-and-drop no calendário.
     *
     * @param  string  $type  Tipo do evento ('activity' | 'prazo')
     * @param  int  $id  ID do registro
     * @param  string  $newStart  Nova data/hora de início (ISO 8601)
     * @param  string|null  $newEnd  Nova data/hora de fim (ISO 8601)
     */
    public function updateEventDate(string $type, int $id, string $newStart, ?string $newEnd = null): bool
    {
        if ($type === 'activity') {
            $activity = Activity::where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (! $activity) {
                return false;
            }

            $activity->schedule_from = Carbon::parse($newStart);
            $activity->schedule_to = $newEnd ? Carbon::parse($newEnd) : Carbon::parse($newStart)->addHour();

            return $activity->save();
        }

        if ($type === 'prazo') {
            $prazo = Prazo::whereHas('processo')
                ->where('id', $id)
                ->first();

            if (! $prazo) {
                return false;
            }

            $prazo->data_vencimento = Carbon::parse($newStart);

            return $prazo->save();
        }

        return false;
    }

    /**
     * Retorna emoji de acordo com o tipo de atividade do Krayin.
     */
    private function getActivityEmoji(string $type): string
    {
        return match ($type) {
            'call'    => '📋',
            'meeting' => '🤝',
            'lunch'   => '⚖️',
            'email'   => '✉️',
            default   => '📋',
        };
    }
}
