<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Models\Payslip;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayslipsRelationManager extends RelationManager
{
    protected static string $relationship = 'payslips';

    protected static ?string $title = 'Bulletins de paie';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->sortable(),
                TextColumn::make('payrollPeriod.name')->label('Période de paie')->sortable(),
                TextColumn::make('gross_total')->label('Salaire brut')->money('MAD')->sortable(),
                TextColumn::make('taxable_gross')->label('Brut imposable')->money('MAD')->toggleable(),
                TextColumn::make('net_to_pay')->label('Net à payer')->money('MAD')->weight('bold')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
                TextColumn::make('generated_at')->label('Généré le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Payslip $record): string => route('payslips.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
