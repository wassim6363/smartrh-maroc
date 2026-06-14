<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use Filament\Widgets\Widget;

class OnboardingWidget extends Widget
{
    protected string $view = 'filament.widgets.onboarding-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -10;

    protected function getViewData(): array
    {
        $user = auth()->user();
        $company = $user?->isSuperAdmin() ? Company::query()->first() : $user?->company;

        if (! $company) {
            return ['company' => null, 'progress' => 0, 'missing' => ['Créer une société']];
        }

        $required = ['name', 'ice', 'rc', 'if', 'cnss_number', 'address', 'city', 'email', 'phone'];
        $missing = collect($required)->filter(fn ($field) => blank($company->{$field}))->values()->all();

        $steps = [
            'Profil société' => count($missing) === 0,
            'Paramètres paie' => $company->payrollSettings()->exists(),
            'Premiers salariés' => Employee::query()->where('company_id', $company->id)->exists(),
            'Période de paie' => PayrollPeriod::query()->where('company_id', $company->id)->exists(),
        ];

        return [
            'company' => $company,
            'steps' => $steps,
            'missing' => $missing,
            'progress' => (int) round((collect($steps)->filter()->count() / count($steps)) * 100),
            'currentDate' => now()->locale('fr')->translatedFormat('l d F Y'),
        ];
    }
}
