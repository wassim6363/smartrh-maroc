<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Services\Saas\SubscriptionLimitService;
use Filament\Widgets\ChartWidget;

class SubscriptionUsageChart extends ChartWidget
{
    protected ?string $heading = 'Utilisation du plan';

    protected ?string $description = 'Salaries, bulletins et contrats consommes sur le mois courant.';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 10;

    protected ?string $maxHeight = '280px';

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
        $user = auth()->user();
        $company = $user?->isSuperAdmin() ? Company::query()->first() : $user?->company;
        $summary = $company ? app(SubscriptionLimitService::class)->getLimitsSummary($company) : null;

        return [
            'datasets' => [[
                'label' => 'Utilises',
                'data' => [
                    $summary['employees']['used'] ?? 0,
                    $summary['payslips']['used'] ?? 0,
                    $summary['contracts']['used'] ?? 0,
                ],
                'backgroundColor' => ['#0F766E', '#14B8A6', '#D4A72C'],
                'borderWidth' => 0,
            ]],
            'labels' => ['Salaries', 'Bulletins', 'Contrats'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
