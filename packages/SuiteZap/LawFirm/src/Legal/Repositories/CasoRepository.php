<?php

namespace SuiteZap\LawFirm\Legal\Repositories;

use SuiteZap\LawFirm\Legal\Models\Caso;
use Webkul\Core\Eloquent\Repository;

class CasoRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Caso::class;
    }
}
