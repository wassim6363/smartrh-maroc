<?php

namespace App\Filament\Pages;

use App\Models\PayrollSetting;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CompanySettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Paramètres société';

    protected string $view = 'filament.pages.company-settings';

    public array $company = [];

    public array $payroll = [];

    public function mount(): void
    {
        $company = $this->currentCompany();
        abort_unless($company, 403);

        $settings = PayrollSetting::query()->firstOrCreate(['company_id' => $company->id], [
            'default_working_hours' => 191,
            'minimum_wage' => 0,
        ]);

        $this->company = $company->only(['name', 'legal_name', 'ice', 'rc', 'if', 'cnss_number', 'address', 'city', 'email', 'phone', 'logo_path']);
        $this->payroll = $settings->only([
            'default_working_hours',
            'default_working_days',
            'payroll_closing_day',
            'currency',
            'payslip_number_prefix',
            'document_number_prefix',
            'email_sender_name',
            'email_sender_address',
            'default_language',
            'timezone',
        ]);
    }

    public function save(): void
    {
        $company = $this->currentCompany();
        abort_unless($company, 403);

        $company->update($this->company);

        PayrollSetting::query()->updateOrCreate(
            ['company_id' => $company->id],
            $this->payroll + [
                'include_cnss' => true,
                'include_amo' => true,
                'include_ir' => true,
            ],
        );

        Notification::make()->title('Paramètres enregistrés')->success()->send();
    }

    private function currentCompany()
    {
        $user = auth()->user();

        if ($user?->isSuperAdmin()) {
            return \App\Models\Company::query()->first();
        }

        return $user?->company;
    }
}
