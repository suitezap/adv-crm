<?php

namespace SuiteZap\LawFirm\SaaS\Observers;

use Illuminate\Support\Facades\Log;
use Webkul\User\Models\User;
use SuiteZap\LawFirm\SaaS\Services\MotherShipService;
use Illuminate\Validation\ValidationException;

/**
 * UserObserver - Enforce SaaS user limits
 * 
 * Intercepts user creation to verify subscription limits
 * before allowing new users to be added.
 */
class UserObserver
{
    /**
     * Handle the User "creating" event.
     * 
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public function creating(User $user)
    {
        // Delega a regra de negócio para o Service (que tem cache e logs) apenas se for ATIVO
        if ($user->status == 1 && !MotherShipService::canCreateUser()) {
            $subscription = MotherShipService::getCurrentSubscription();
            $max = $subscription ? $subscription->max_users : 0;

            // Usa ValidationException para retornar ao formulário com erro amigável
            // em vez de gerar uma página de erro 500
            throw ValidationException::withMessages([
                'name' => ["⛔ ATENÇÃO: Seu plano permite apenas {$max} usuários ativos. Faça upgrade para adicionar mais."]
            ]);
        }
    }

    /**
     * Handle the User "updating" event.
     * 
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public function updating(User $user)
    {
        // Só bloqueia quando ocorre transição real: inativo (0) → ativo (1)
        // Usar getOriginal() é mais confiável que isDirty() pois o formulário
        // do Krayin (Vue) pode reenviar o mesmo valor, gerando falsos isDirty.
        $wasInactive = (int) $user->getOriginal('status') !== 1;
        $isBecomingActive = (int) $user->status === 1;

        if ($wasInactive && $isBecomingActive && !MotherShipService::canCreateUser()) {
            $subscription = MotherShipService::getCurrentSubscription();
            $max = $subscription ? $subscription->max_users : 0;

            throw ValidationException::withMessages([
                'name' => ["⛔ ATENÇÃO: Seu plano permite apenas {$max} usuários ativos. Faça upgrade para adicionar mais."]
            ]);
        }
    }
}
