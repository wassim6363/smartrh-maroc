<?php

namespace App\Filament\Widgets;

use App\Models\Payslip;
use Filament\Widgets\ChartWidget;

class PayrollMassChart extends ChartWidget
{
    protected ?string $heading = 'Masse salariale sur 6 mois';

    protected ?string $description = 'Total net à payer, mois par mois.';

    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    protected ?array $options = [
        'plugins' => [
            'legend' => ['display' => false],
        ],
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
        $months = collect(range(5, 0))
            ->map(fn (int $monthsAgo) => now()->subMonths($monthsAgo)->startOfMonth());

        $values = $months
            ->map(fn ($month) => (float) Payslip::query()
                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->sum('net_pay'))
            ->values();

        if (config('smartrh.demo_mode_enabled') && $values->filter(fn ($value) => $value > 0)->count() <= 1) {
            $base = max((float) $values->max(), 42000);
            $values = collect([0.78, 0.84, 0.91, 0.88, 0.96, 1.0])->map(fn ($ratio) => round($base * $ratio, 2));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Net à payer (MAD)',
                    'data' => $values->all(),
                    'borderColor' => '#0F766E',
                    'backgroundColor' => 'rgba(20, 184, 166, 0.14)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $months->map(fn ($month) => $month->locale('fr')->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
