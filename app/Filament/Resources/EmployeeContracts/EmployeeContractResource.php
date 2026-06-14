<?php

namespace App\Filament\Resources\EmployeeContracts;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\EmployeeContracts\Pages\CreateEmployeeContract;
use App\Filament\Resources\EmployeeContracts\Pages\EditEmployeeContract;
use App\Filament\Resources\EmployeeContracts\Pages\ListEmployeeContracts;
use App\Filament\Resources\EmployeeContracts\Pages\ViewEmployeeContract;
use App\Filament\Resources\EmployeeContracts\Schemas\EmployeeContractForm;
use App\Filament\Resources\EmployeeContracts\Schemas\EmployeeContractInfolist;
use App\Filament\Resources\EmployeeContracts\Tables\EmployeeContractsTable;
use App\Models\EmployeeContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmployeeContractResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = EmployeeContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Contrats employés';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return EmployeeContractForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeContractInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeContractsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeContracts::route('/'),
            'create' => CreateEmployeeContract::route('/create'),
            'view' => ViewEmployeeContract::route('/{record}'),
            'edit' => EditEmployeeContract::route('/{record}/edit'),
        ];
    }

    public static function typeOptions(): array
    {
        return [
            'CDI' => 'CDI',
            'CDD' => 'CDD',
            'FREELANCE' => 'Freelance / Prestation',
            'ANAPEC' => 'ANAPEC',
            'STAGE' => 'Convention de stage',
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
        ];
    }
}
