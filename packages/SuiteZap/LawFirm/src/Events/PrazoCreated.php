<?php

namespace SuiteZap\LawFirm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SuiteZap\LawFirm\Models\Prazo;

class PrazoCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The Prazo instance.
     *
     * @var \SuiteZap\LawFirm\Models\Prazo
     */
    public $prazo;

    /**
     * Create a new event instance.
     *
     * @param  \SuiteZap\LawFirm\Models\Prazo  $prazo
     * @return void
     */
    public function __construct(Prazo $prazo)
    {
        $this->prazo = $prazo;
    }
}
