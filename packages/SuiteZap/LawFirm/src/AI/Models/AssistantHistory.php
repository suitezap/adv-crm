<?php

namespace SuiteZap\LawFirm\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\User\Models\User;
use Webkul\Lead\Models\Lead;
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
        'lead_id',
        'template_id',
        'input_data',
        'generated_content',
        'execution_mode',
        'status',
        'execution_id',
        'node_name',
        'model',
        'total_cost',
        'real_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'input_data' => 'array',
        'total_cost' => 'decimal:4',
        'real_cost' => 'decimal:4',
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

    /**
     * Get the lead associated with this history entry.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
