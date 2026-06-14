<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DepartmentDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Répartition des salariés par département';

    protected ?string $description = 'Vision rapide de la structure RH.';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 6;

    protected ?string $maxHeight = '300px';

    protected ?array $options = [
        'plugins' => [
            'legend' => [
                'labels' => ['color' => '#CBD5E1'],
            ],
        ],
    ];

    protected function getData(): array
    {
        $rows = Employee::query()
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->selectRaw('coalesce(departments.name, ?) as label, count(*) as total', ['Sans département'])
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Salariés',
                'data' => $rows->pluck('total')->map(fn ($value) => (int) $value)->all(),
                'backgroundColor' => ['#0F766E', '#14B8A6', '#D4A72C', '#0F3D3E', '#F3C969', '#16A34A', '#2B3A52', '#94A3B8'],
                'borderWidth' => 0,
            ]],
            'labels' => $rows->pluck('label')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
