<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Filament\Widgets\ChartWidget;

class LeaveStatusChart extends ChartWidget
{
    protected ?string $heading = 'Statut des demandes de congé';

    protected ?string $description = 'Demandes en attente, validées et refusées.';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 7;

    protected ?string $maxHeight = '300px';

    protected ?array $options = [
        'plugins' => ['legend' => ['display' => false]],
        'scales' => [
            'x' => [
                'grid' => ['color' => '#2B3A52'],
                'ticks' => ['color' => '#CBD5E1'],
            ],
            'y' => [
                'beginAtZero' => true,
                'grid' => ['color' => '#2B3A52'],
                'ticks' => ['color' => '#CBD5E1'],
            ],
        ],
    ];

    protected function getData(): array
    {
        $labels = ['En attente', 'Acceptées', 'Refusées'];
        $statuses = ['pending', 'approved', 'rejected'];

        return [
            'datasets' => [[
                'label' => 'Demandes',
                'data' => collect($statuses)->map(fn ($status) => LeaveRequest::query()->where('status', $status)->count())->all(),
                'backgroundColor' => ['#D4A72C', '#16A34A', '#DC2626'],
                'borderRadius' => 6,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
