<?php

namespace SuiteZap\LawFirm\Observers;

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
        // Delega a regra de negócio para o Service (que tem cache e logs)
        if (!MotherShipService::canCreateUser()) {
            $subscription = MotherShipService::getCurrentSubscription();
            $max = $subscription ? $subscription->max_users : 0;

            // Usa ValidationException para retornar ao formulário com erro amigável
            // em vez de gerar uma página de erro 500
            throw ValidationException::withMessages([
                'email' => ["⛔ ATENÇÃO: Seu plano permite apenas {$max} usuários ativos. Faça upgrade para adicionar mais."]
            ]);
        }
    }
}
