<?php

namespace App\Filament\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                Select::make('employee_id')->relationship('employee', 'employee_number')->searchable()->preload()->required(),
                Select::make('leave_type_id')->relationship('leaveType', 'name')->searchable()->preload()->required(),
                DatePicker::make('starts_at')->required(),
                DatePicker::make('ends_at')->required(),
                TextInput::make('days')->numeric()->required(),
                Select::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'])
                    ->default('pending')
                    ->required(),
                Textarea::make('reason')->columnSpanFull(),
            ]);
    }
}
