<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl">
                <div class="mb-3 flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600 text-sm font-black text-white shadow-sm">
                        SR
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">Mode démo · Données fictives</p>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Bienvenue sur {{ config('smartrh.product_name') }}</h2>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">Pilotez vos RH, votre paie, vos documents et votre portail salarié depuis un espace SaaS clair et sécurisé.</p>
                <p class="smartrh-dashboard-meta mt-2">
                    {{ ucfirst($currentDate ?? now()->format('d/m/Y')) }}
                    @if ($company)
                        · Société: {{ $company->name }}
                    @endif
                    · Support: {{ config('smartrh.support_phone') }}
                </p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full bg-primary-600" style="width: {{ $progress }}%"></div>
                </div>
                <p class="mt-2 text-xs font-medium text-gray-500">Progression de configuration: {{ $progress }}%</p>
                @if (! empty($missing))
                    <p class="mt-3 text-sm text-warning-600">Champs société à compléter: {{ implode(', ', $missing) }}</p>
                @endif
            </div>

            <div class="grid min-w-72 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                <x-filament::button tag="a" href="/admin/employees/create" icon="heroicon-m-user-plus">Ajouter un salarié</x-filament::button>
                <x-filament::button tag="a" href="/admin/payroll-periods/create" color="gray" icon="heroicon-m-calendar-days">Créer une période</x-filament::button>
                <x-filament::button tag="a" href="/admin/payroll-periods" color="success" icon="heroicon-m-calculator">Générer les bulletins</x-filament::button>
                <x-filament::button tag="a" href="/admin/legal-settings" color="gray" icon="heroicon-m-scale">Paramètres légaux</x-filament::button>
                <x-filament::button tag="a" href="/employee_import_template.csv" color="gray" icon="heroicon-m-arrow-down-tray">Importer des salariés</x-filament::button>
                <x-filament::button tag="a" href="/admin/demo-requests" color="gray" icon="heroicon-m-chat-bubble-left-right">Demandes de démo</x-filament::button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
