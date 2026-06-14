<?php

namespace App\Filament\Resources\EmployeePayrollItems;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\EmployeePayrollItems\Pages\CreateEmployeePayrollItem;
use App\Filament\Resources\EmployeePayrollItems\Pages\EditEmployeePayrollItem;
use App\Filament\Resources\EmployeePayrollItems\Pages\ListEmployeePayrollItems;
use App\Models\EmployeePayrollItem;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeePayrollItemResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = EmployeePayrollItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Éléments de paie salarié';

    protected static string|\UnitEnum|null $navigationGroup = 'Paie';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rubrique de paie')
                ->description('Si cette rubrique est exonérée, décochez CNSS, AMO, IR et cochez Exonérée. Sinon, elle sera incluse dans le brut imposable.')
                ->columns(2)
                ->schema([
                    Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload()->required(),
                    Select::make('employee_id')
                        ->label('Salarié')
                        ->relationship(
                            'employee',
                            'employee_number',
                            modifyQueryUsing: fn (Builder $query) => $query->when(! auth()->user()?->isSuperAdmin(), fn (Builder $query) => $query->where('company_id', auth()->user()?->currentCompanyId()))
                        )
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->employee_number . ' - ' . $record->full_name)
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('label')->label('Libellé')->required()->maxLength(255),
                    TextInput::make('code')->label('Code')->maxLength(50),
                    Select::make('type')->label('Type')->required()->options(self::typeOptions())->default('earning'),
                    TextInput::make('amount')->label('Montant')->numeric()->required()->prefix('MAD'),
                    Toggle::make('subject_to_cnss')->label('Soumis à CNSS')->default(true),
                    Toggle::make('subject_to_amo')->label('Soumis à AMO')->default(true),
                    Toggle::make('subject_to_ir')->label('Soumis à IR')->default(true),
                    Toggle::make('is_tax_exempt')
                        ->label('Exonérée')
                        ->helperText('Si cette rubrique est exonérée, décochez CNSS, AMO, IR et cochez Exonérée. Sinon, elle sera incluse dans le brut imposable.')
                        ->default(false),
                    Toggle::make('recurring')->label('Récurrente')->default(false),
                    Toggle::make('active')->label('Active')->default(true),
                    DatePicker::make('starts_at')->label('Date début'),
                    DatePicker::make('ends_at')->label('Date fin'),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('label')->label('Libellé')->searchable()->sortable(),
                TextColumn::make('amount')->label('Montant')->money('MAD')->sortable(),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),
                IconColumn::make('subject_to_cnss')->label('CNSS')->boolean(),
                IconColumn::make('subject_to_amo')->label('AMO')->boolean(),
                IconColumn::make('subject_to_ir')->label('IR')->boolean(),
                IconColumn::make('is_tax_exempt')->label('Exonérée')->boolean(),
                IconColumn::make('active')->label('Active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société'),
                SelectFilter::make('type')->label('Type')->options(self::typeOptions()),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeePayrollItems::route('/'),
            'create' => CreateEmployeePayrollItem::route('/create'),
            'edit' => EditEmployeePayrollItem::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'earning' => 'Gain',
            'deduction' => 'Retenue',
            'prime' => 'Prime imposable (ancien)',
            'indemnity_taxable' => 'Indemnité imposable (ancien)',
            'indemnity_non_taxable' => 'Indemnité exonérée (ancien)',
            'overtime' => 'Heures supplémentaires (ancien)',
            'advance' => 'Avance (ancien)',
            'other' => 'Autre retenue (ancien)',
        ];
    }
}
