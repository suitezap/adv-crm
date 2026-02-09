<?php

namespace SuiteZap\LawFirm\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SuiteZap\LawFirm\Events\PrazoCreated;
use SuiteZap\LawFirm\Listeners\SendPrazoWhatsapp;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

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
        // ---------------------------------------------------------------------
        // ---------------------------------------------------------------------
        // Injection: LawFirm Checklist Panel (Standalone)
        // ---------------------------------------------------------------------
        Event::listen('admin.leads.view.activities.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('lawfirm::leads.checklist-tab-injection');
            Log::debug('LawFirm: Injected Checklist Panel (Activities Before Hook)');
        });
    }
}
