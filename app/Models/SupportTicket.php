<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->assignedUser();
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statuses()[$this->status] ?? (string) $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::priorities()[$this->priority] ?? (string) $this->priority;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category] ?? (string) $this->category;
    }

    public static function statuses(): array
    {
        return [
            'open' => 'Ouvert',
            'in_progress' => 'En cours',
            'waiting_customer' => 'En attente client',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low' => 'Basse',
            'normal' => 'Normale',
            'high' => 'Haute',
            'urgent' => 'Urgente',
        ];
    }

    public static function categories(): array
    {
        return [
            'technical' => 'Technique',
            'payroll' => 'Paie',
            'billing' => 'Facturation',
            'contract' => 'Contrats',
            'account' => 'Compte',
            'other' => 'Autre',
        ];
    }
}
