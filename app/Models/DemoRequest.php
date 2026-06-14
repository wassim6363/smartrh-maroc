<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'contacted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_company_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
