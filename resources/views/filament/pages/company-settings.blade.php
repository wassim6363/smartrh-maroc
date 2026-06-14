<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <x-filament::section heading="Informations société">
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper><x-filament::input placeholder="Nom société" wire:model="company.name" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Raison sociale" wire:model="company.legal_name" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="ICE" wire:model="company.ice" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="RC" wire:model="company.rc" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="IF" wire:model="company.if" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Numéro CNSS" wire:model="company.cnss_number" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Adresse" wire:model="company.address" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Ville" wire:model="company.city" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Email" wire:model="company.email" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Téléphone" wire:model="company.phone" /></x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <x-filament::section heading="Paramètres paie">
            <p class="mb-4 text-sm text-gray-500">Les règles CNSS, AMO, IR et frais professionnels sont configurables et doivent être validées par un expert-comptable marocain avant production.</p>
            <div class="grid gap-4 md:grid-cols-3">
                <x-filament::input.wrapper><x-filament::input type="number" placeholder="Heures mensuelles" wire:model="payroll.default_working_hours" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input type="number" placeholder="Jours ouvrés" wire:model="payroll.default_working_days" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input type="number" placeholder="Jour clôture paie" wire:model="payroll.payroll_closing_day" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Devise" wire:model="payroll.currency" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Préfixe bulletin" wire:model="payroll.payslip_number_prefix" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Préfixe document" wire:model="payroll.document_number_prefix" /></x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <x-filament::section heading="Email, documents et sécurité">
            <div class="grid gap-4 md:grid-cols-2">
                <x-filament::input.wrapper><x-filament::input placeholder="Nom expéditeur" wire:model="payroll.email_sender_name" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Email expéditeur" wire:model="payroll.email_sender_address" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Langue par défaut" wire:model="payroll.default_language" /></x-filament::input.wrapper>
                <x-filament::input.wrapper><x-filament::input placeholder="Fuseau horaire" wire:model="payroll.timezone" /></x-filament::input.wrapper>
            </div>
        </x-filament::section>

        <x-filament::button type="submit">Enregistrer les paramètres</x-filament::button>
    </form>
</x-filament-panels::page>
