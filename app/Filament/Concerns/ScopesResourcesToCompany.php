<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait ScopesResourcesToCompany
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $companyId = $user->currentCompanyId();
        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        $model = $query->getModel();
        $table = $model->getTable();

        if ($table === 'companies') {
            return $query->whereKey($companyId);
        }

        if (in_array('company_id', $model->getFillable(), true) || method_exists($model, 'company')) {
            return $query->where($table . '.company_id', $companyId);
        }

        return $query;
    }
}
