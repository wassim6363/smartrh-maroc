<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EmployeeContract extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'salary' => 'decimal:2',
        'generated_at' => 'datetime',
        'signed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class);
    }

    public function generatedDocuments(): MorphMany
    {
        return $this->morphMany(GeneratedDocument::class, 'documentable');
    }
}
