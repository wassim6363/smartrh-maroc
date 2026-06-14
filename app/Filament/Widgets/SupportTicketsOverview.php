<?php

namespace App\Filament\Widgets;

use App\Models\SupportTicket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SupportTicketsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Support clients';

    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        return [
            Stat::make('Tickets ouverts', SupportTicket::query()->where('status', 'open')->count())
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('warning'),
            Stat::make('Tickets urgents', SupportTicket::query()->where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count())
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('En cours', SupportTicket::query()->where('status', 'in_progress')->count())
                ->icon('heroicon-o-clock')
                ->color('info'),
            Stat::make('Résolus ce mois', SupportTicket::query()->where('status', 'resolved')->whereBetween('resolved_at', [now()->startOfMonth(), now()->endOfMonth()])->count())
                ->icon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
