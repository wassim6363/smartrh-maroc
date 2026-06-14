<?php

namespace App\Filament\Resources\GeneratedDocuments;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\GeneratedDocuments\Pages\ListGeneratedDocuments;
use App\Models\GeneratedDocument;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GeneratedDocumentResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = GeneratedDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Documents générés';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Société')->relationship('company', 'name')->required(),
            Select::make('employee_id')->label('Salarié')->relationship('employee', 'employee_number'),
            TextInput::make('type')->label('Type')->required(),
            TextInput::make('title')->label('Titre')->required(),
            TextInput::make('reference')->label('Référence'),
            Select::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'generated' => 'Généré', 'archived' => 'Archivé'])->default('generated'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->searchable()->sortable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('reference')->label('Référence')->searchable()->toggleable(),
                TextColumn::make('type')->label('Type')->badge()->sortable(),
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
                TextColumn::make('generated_at')->label('Généré le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société')->searchable()->preload(),
                SelectFilter::make('employee_id')->relationship('employee', 'employee_number')->label('Salarié')->searchable()->preload(),
                SelectFilter::make('type')->label('Type')->options([
                    'payslip' => 'Bulletin de paie',
                    'CDI' => 'CDI',
                    'CDD' => 'CDD',
                    'FREELANCE' => 'Freelance',
                    'ANAPEC' => 'ANAPEC',
                    'STAGE' => 'Stage',
                    'AVENANT' => 'Avenant',
                    'ATTESTATION_TRAVAIL' => 'Attestation de travail',
                    'CERTIFICAT_TRAVAIL' => 'Certificat de travail',
                    'SOLDE_TOUT_COMPTE' => 'Solde de tout compte',
                    'ATTESTATION_SALAIRE' => 'Attestation de salaire',
                    'ATTESTATION_CONGE' => 'Attestation de congé',
                    'RECU_PAIEMENT' => 'Reçu de paiement',
                    'DECISION_SANCTION' => 'Décision de sanction',
                    'LETTRE_DEMISSION' => 'Lettre de démission',
                    'LETTRE_LICENCIEMENT' => 'Lettre de licenciement',
                    'CONVOCATION_ENTRETIEN' => 'Convocation à entretien',
                    'AUTORISATION_ABSENCE' => 'Autorisation d’absence',
                ]),
                SelectFilter::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'generated' => 'Généré', 'archived' => 'Archivé']),
            ])
            ->recordActions([
                Action::make('viewContent')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (GeneratedDocument $record): string => $record->title)
                    ->modalContent(fn (GeneratedDocument $record): \Illuminate\Support\HtmlString => new \Illuminate\Support\HtmlString('<div class="prose max-w-none">' . ($record->content_html ?: 'Aucun contenu HTML disponible.') . '</div>'))
                    ->modalSubmitAction(false),
                Action::make('download')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (GeneratedDocument $record) => route('documents.download', $record))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucun document généré');
    }

    public static function getPages(): array
    {
        return ['index' => ListGeneratedDocuments::route('/')];
    }
}
