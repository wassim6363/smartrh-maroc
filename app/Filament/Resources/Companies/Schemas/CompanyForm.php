<?php

namespace App\Filament\Resources\Companies\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')->required()->maxLength(255),
                        TextInput::make('legal_name')->maxLength(255),
                        TextInput::make('ice')->label('ICE')->maxLength(255),
                        TextInput::make('rc')->label('RC')->maxLength(255),
                        TextInput::make('if')->label('IF')->maxLength(255),
                        TextInput::make('cnss_number')->label('CNSS number')->maxLength(255),
                        TextInput::make('email')->email()->maxLength(255),
                        TextInput::make('phone')->tel()->maxLength(255),
                        FileUpload::make('logo_path')->label('Logo')->image()->directory('companies/logos'),
                        TextInput::make('address')->columnSpanFull()->maxLength(255),
                        TextInput::make('city')->maxLength(255),
                        TextInput::make('country')->default('Morocco')->maxLength(255),
                        Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }
}
