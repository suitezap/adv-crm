<?php

namespace SuiteZap\LawFirm\Legal\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SuiteZap\LawFirm\Legal\Models\Prazo;

class PrazoCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The Prazo instance.
     *
     * @var Prazo
     */
    public $prazo;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Prazo $prazo)
    {
        $this->prazo = $prazo;
    }
}
