<?php

namespace SuiteZap\LawFirm\Models\MotherShip;

use Illuminate\Database\Eloquent\Model;

class ProductModule extends Model
{
    protected $connection = 'mothership';
    protected $table = 'products_modules';
    protected $guarded = [];
}
