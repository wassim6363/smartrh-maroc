<?php

namespace App\Filament\Resources\Positions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PositionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                Select::make('department_id')->relationship('department', 'name')->searchable()->preload(),
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('min_salary')->numeric()->prefix('MAD'),
                TextInput::make('max_salary')->numeric()->prefix('MAD'),
                Textarea::make('description')->columnSpanFull(),
            ]);
    }
}
