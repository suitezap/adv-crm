<?php

namespace SuiteZap\LawFirm\Legal\Repositories;

use SuiteZap\LawFirm\Legal\Models\Processo;
use Webkul\Core\Eloquent\Repository;

class ProcessoRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return Processo::class;
    }
}
