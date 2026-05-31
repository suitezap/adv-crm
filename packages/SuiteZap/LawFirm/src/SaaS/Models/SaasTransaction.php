<?php

namespace SuiteZap\LawFirm\SaaS\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Webkul\User\Models\User;

class SaasTransaction extends Model
{
    protected $table = 'saas_transactions';

    protected $fillable = [
        'tenant_id',
        'type',
        'amount',
        'balance_after',
        'currency',
        'service_type',
        'description',
        'user_id',
        'reference_id',
        'reference_type',
    ];

    protected $attributes = [
        'currency' => 'SUITECOIN',
    ];

    /**
     * The model that originated the transaction.
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who triggered the transaction.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
