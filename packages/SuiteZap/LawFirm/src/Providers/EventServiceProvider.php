<?php

namespace SuiteZap\LawFirm\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SuiteZap\LawFirm\Events\PrazoCreated;
use SuiteZap\LawFirm\Listeners\SendPrazoWhatsapp;

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
    }
}
