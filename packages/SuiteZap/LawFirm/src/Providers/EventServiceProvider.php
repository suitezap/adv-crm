<?php

namespace SuiteZap\LawFirm\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use SuiteZap\LawFirm\Events\PrazoCreated;
use SuiteZap\LawFirm\Legal\Events\CasoStageUpdated;
use SuiteZap\LawFirm\Legal\Listeners\SyncCasoStageToChatwootListener;
use SuiteZap\LawFirm\Whatsapp\Listeners\SendPrazoWhatsapp;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        PrazoCreated::class => [
            SendPrazoWhatsapp::class,
        ],
        CasoStageUpdated::class => [
            SyncCasoStageToChatwootListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // ---------------------------------------------------------------------
        // Injection: LawFirm Checklist Tab (Frontend Bridge)
        // DISABLED — Checklist will be rebuilt from scratch
        // ---------------------------------------------------------------------
        // Event::listen('admin.leads.view.activities.before', function ($viewRenderEventManager) {
        //     $viewRenderEventManager->addTemplate('lawfirm::leads.checklist-tab-injection');
        //     Log::debug('LawFirm: Injected Checklist Panel (Activities Before Hook)');
        // });

        // ---------------------------------------------------------------------
        // Injection: Pre-Triagem IA Button (Lead Header Actions)
        // DISABLED — Will be rebuilt with the new Checklist
        // ---------------------------------------------------------------------
        // Event::listen('admin.leads.view.actions.after', function ($viewRenderEventManager) {
        //     $viewRenderEventManager->addTemplate('lawfirm::leads.pre-triagem-button');
        // });

        // ---------------------------------------------------------------------
        // Injection: Lead Tools Panel (AI Actions — below pipeline stages)
        // ---------------------------------------------------------------------
        Event::listen('admin.leads.view.stages.after', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::leads.lead-tools-panel');
        });
    }
}
