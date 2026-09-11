<?php

namespace SuiteZap\LawFirm\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;

class HumanDecision extends Model
{
    protected $table = 'lawfirm_human_decisions';

    protected $guarded = [];

    public function aiExecution()
    {
        return $this->belongsTo(AiExecution::class, 'execution_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
