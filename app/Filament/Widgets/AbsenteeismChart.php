<?php

namespace App\Filament\Widgets;

use App\Models\Absence;
use Filament\Widgets\ChartWidget;

class AbsenteeismChart extends ChartWidget
{
    protected ?string $heading = 'Absentéisme du mois';

    protected ?string $description = 'Nombre d’absences et retards par semaine.';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 9;

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
        $weeks = collect(range(0, 3))->map(fn (int $week) => now()->startOfMonth()->addWeeks($week));

        return [
            'datasets' => [[
                'label' => 'Absences',
                'data' => $weeks->map(fn ($week) => Absence::query()
                    ->whereBetween('date', [$week->copy()->startOfWeek(), $week->copy()->endOfWeek()])
                    ->count())->all(),
                'borderColor' => '#DC2626',
                'backgroundColor' => 'rgba(220, 38, 38, 0.14)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $weeks->map(fn ($week, $index) => 'Semaine ' . ($index + 1))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
