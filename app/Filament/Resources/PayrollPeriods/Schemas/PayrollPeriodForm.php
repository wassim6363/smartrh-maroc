<?php

namespace App\Filament\Resources\PayrollPeriods\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PayrollPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                TextInput::make('name')->required()->maxLength(255),
                DatePicker::make('starts_at')->required(),
                DatePicker::make('ends_at')->required(),
                DatePicker::make('payment_date'),
                Select::make('status')
                    ->options(['draft' => 'Brouillon', 'generated' => 'Généré', 'validated' => 'Validé', 'sent' => 'Envoyé', 'closed' => 'Clôturé', 'cancelled' => 'Annulé'])
                    ->default('draft')
                    ->required(),
            ]);
    }
}
