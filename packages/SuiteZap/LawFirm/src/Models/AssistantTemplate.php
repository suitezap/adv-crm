<?php

namespace SuiteZap\LawFirm\Models;

use Illuminate\Database\Eloquent\Model;
use SuiteZap\LawFirm\Contracts\AssistantTemplate as AssistantTemplateContract;

class AssistantTemplate extends Model implements AssistantTemplateContract
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lawfirm_assistant_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'category',
        'description',
        'form_schema',
        'prompt_structure',
        'n8n_webhook_url',
        'token_cost',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'form_schema' => 'array',
        'is_active' => 'boolean',
    ];
}
