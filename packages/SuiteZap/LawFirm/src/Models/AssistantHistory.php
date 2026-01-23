<?php

namespace SuiteZap\LawFirm\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;
use SuiteZap\LawFirm\Contracts\AssistantHistory as AssistantHistoryContract;

class AssistantHistory extends Model implements AssistantHistoryContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lawfirm_assistant_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'template_id',
        'input_data',
        'generated_content',
        'execution_mode',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'input_data' => 'array',
    ];

    /**
     * Get the template that owns the history.
     */
    public function template()
    {
        return $this->belongsTo(AssistantTemplate::class, 'template_id');
    }

    /**
     * Get the user that owns the history.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
