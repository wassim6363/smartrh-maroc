<?php

namespace App\Filament\Resources\ContractTemplates;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\ContractTemplates\Pages\CreateContractTemplate;
use App\Filament\Resources\ContractTemplates\Pages\EditContractTemplate;
use App\Filament\Resources\ContractTemplates\Pages\ListContractTemplates;
use App\Models\ContractTemplate;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ContractTemplateResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = ContractTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Modèles de contrats';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload(),
            Select::make('type')->label('Type de contrat')->options(self::typeOptions())->required(),
            TextInput::make('title')->label('Titre')->required()->maxLength(255),
            TextInput::make('name')->label('Nom interne')->maxLength(255),
            Select::make('language')->label('Langue')->options(['fr' => 'Français', 'ar' => 'Arabe'])->default('fr')->required(),
            Toggle::make('is_default')->label('Modèle par défaut')->default(false),
            Toggle::make('is_active')->label('Actif')->default(true),
            RichEditor::make('content_html')->label('Contenu HTML')->columnSpanFull()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->placeholder('Global')->searchable()->sortable(),
                TextColumn::make('title')->label('Titre')->searchable()->sortable(),
                TextColumn::make('type')->label('Type de contrat')->badge()->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),
                TextColumn::make('language')->label('Langue')->badge(),
                IconColumn::make('is_default')->label('Défaut')->boolean(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société')->searchable()->preload(),
                SelectFilter::make('type')->label('Type de contrat')->options(self::typeOptions()),
                SelectFilter::make('language')->label('Langue')->options(['fr' => 'Français', 'ar' => 'Arabe']),
                SelectFilter::make('is_active')->label('Actif')->options([1 => 'Actif', 0 => 'Inactif']),
            ])
            ->recordActions([
                Action::make('preview')
                    ->label('Prévisualiser')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn (ContractTemplate $record): string => $record->title)
                    ->modalContent(fn (ContractTemplate $record): HtmlString => new HtmlString('<div class="prose max-w-none">' . ($record->content_html ?: $record->body) . '</div>'))
                    ->modalSubmitAction(false),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContractTemplates::route('/'),
            'create' => CreateContractTemplate::route('/create'),
            'edit' => EditContractTemplate::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'CDI' => 'CDI',
            'CDD' => 'CDD',
            'FREELANCE' => 'Contrat Freelance / Prestation',
            'ANAPEC' => 'Contrat ANAPEC',
            'STAGE' => 'Convention de stage',
            'AVENANT' => 'Avenant au contrat',
            'ATTESTATION_TRAVAIL' => 'Attestation de travail',
            'CERTIFICAT_TRAVAIL' => 'Certificat de travail',
            'SOLDE_TOUT_COMPTE' => 'Solde de tout compte',
        ];
    }
}
