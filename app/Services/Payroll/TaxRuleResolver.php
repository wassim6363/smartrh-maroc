<?php

namespace App\Services\Payroll;

use App\Models\AmoRate;
use App\Models\CnssRate;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\ProfessionalExpenseRate;
use App\Models\SeniorityBonusRate;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TaxRuleResolver
{
    /**
     * Payroll rules are configurable demo data. Official Moroccan payroll rules
     * must be verified by a qualified accountant before production use.
     */
    public function missingRules(int $companyId, CarbonInterface $date): array
    {
        return collect([
            'Paramètres légaux' => $this->legalSetting($date),
            'CNSS' => $this->cnssRate($companyId, $date),
            'AMO' => $this->amoRate($companyId, $date),
            'Frais professionnels' => $this->professionalExpenseRate($companyId, $date),
            'Barème IR' => $this->irBrackets($companyId, $date)->isNotEmpty(),
        ])
            ->filter(fn ($rule) => blank($rule))
            ->keys()
            ->all();
    }

    public function legalSetting(CarbonInterface $date): ?LegalSetting
    {
        return LegalSetting::query()
            ->where('active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByRaw('year is null')
            ->orderByDesc('effective_from')
            ->first();
    }

    public function cnssRate(int $companyId, CarbonInterface $date): ?CnssRate
    {
        return $this->activeRule(CnssRate::query(), $companyId, $date);
    }

    public function amoRate(int $companyId, CarbonInterface $date): ?AmoRate
    {
        return $this->activeRule(AmoRate::query(), $companyId, $date);
    }

    public function professionalExpenseRate(int $companyId, CarbonInterface $date): ?ProfessionalExpenseRate
    {
        return $this->activeRule(ProfessionalExpenseRate::query(), $companyId, $date);
    }

    public function seniorityBonusRate(int $completedYears, CarbonInterface $date): ?SeniorityBonusRate
    {
        return SeniorityBonusRate::query()
            ->where('active', true)
            ->where('min_years', '<=', $completedYears)
            ->where(function (Builder $query) use ($completedYears) {
                $query->whereNull('max_years')->orWhere('max_years', '>', $completedYears);
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('min_years')
            ->first();
    }

    public function irBracket(int $companyId, float $monthlyTaxableIncome, CarbonInterface $date): ?IrBracket
    {
        return IrBracket::query()
            ->where(function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where(function (Builder $query) {
                $query->where('active', true)->orWhereNull('active');
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->where('min_amount', '<=', $monthlyTaxableIncome)
            ->where(function (Builder $query) use ($monthlyTaxableIncome) {
                $query->whereNull('max_amount')->orWhere('max_amount', '>=', $monthlyTaxableIncome);
            })
            ->orderByRaw('company_id is null')
            ->orderByDesc('min_amount')
            ->first();
    }

    public function irBrackets(int $companyId, CarbonInterface $date): Collection
    {
        return IrBracket::query()
            ->where(function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where(function (Builder $query) {
                $query->where('active', true)->orWhereNull('active');
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderBy('min_amount')
            ->get();
    }

    private function activeRule(Builder $query, int $companyId, CarbonInterface $date): ?Model
    {
        return $query
            ->where(function (Builder $query) use ($companyId) {
                $query->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByRaw('company_id is null')
            ->latest('effective_from')
            ->first();
    }
}
