<?php

namespace App\Filament\Widgets;

use App\Models\Payslip;
use Filament\Widgets\ChartWidget;

class PayslipStatusChart extends ChartWidget
{
    protected ?string $heading = 'Bulletins de paie par statut';

    protected ?string $description = 'Suivi du cycle de validation paie.';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 8;

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
        $labels = ['Brouillon', 'Généré', 'Validé', 'Envoyé', 'Clôturé', 'Annulé'];
        $statuses = ['draft', 'generated', 'validated', 'sent', 'closed', 'cancelled'];

        return [
            'datasets' => [[
                'label' => 'Bulletins',
                'data' => collect($statuses)->map(fn ($status) => Payslip::query()->where('status', $status)->count())->all(),
                'backgroundColor' => ['#94A3B8', '#D4A72C', '#16A34A', '#14B8A6', '#2B3A52', '#DC2626'],
                'borderWidth' => 0,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
