<x-filament-widgets::widget>
    <x-filament::section heading="Abonnement SaaS">
        @if ($company && $subscription && $summary)
            <div class="space-y-4">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-lg font-semibold text-gray-950 dark:text-white">{{ $summary['plan'] }} - {{ ucfirst($summary['status']) }}</p>
                        <p class="text-sm text-gray-500">Periode: {{ $subscription->current_period_start?->format('d/m/Y') ?: '-' }} au {{ $subscription->current_period_end?->format('d/m/Y') ?: '-' }}</p>
                    </div>
                    <x-filament::button tag="a" href="/demo" color="{{ $limitReached ? 'warning' : 'success' }}" icon="heroicon-m-arrow-trending-up">
                        {{ $limitReached ? 'Mettre a niveau' : 'Voir les plans' }}
                    </x-filament::button>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    @foreach (['employees' => 'Salaries', 'payslips' => 'Bulletins ce mois', 'contracts' => 'Contrats ce mois'] as $key => $label)
                        @php($item = $summary[$key])
                        @php($limit = $item['limit'] ?: null)
                        @php($percent = $limit ? min(100, round(($item['used'] / max($limit, 1)) * 100)) : 0)
                        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $label }}</span>
                                <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $item['used'] }} / {{ $limit ?: 'illimite' }}</span>
                            </div>
                            @if ($limit)
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                                    <div class="h-full rounded-full bg-success-500" style="width: {{ $percent }}%"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($limitReached)
                    <p class="rounded-lg bg-warning-50 p-3 text-sm font-medium text-warning-700 dark:bg-warning-500/10">
                        Vous avez atteint la limite de votre abonnement. Veuillez passer à un pack supérieur.
                    </p>
                @elseif ($nearLimit)
                    <p class="rounded-lg bg-warning-50 p-3 text-sm font-medium text-warning-700 dark:bg-warning-500/10">
                        Utilisation actuelle de votre abonnement: vous approchez d'une limite du pack.
                    </p>
                @endif
                @if ($trialExpiringSoon)
                    <p class="rounded-lg bg-warning-50 p-3 text-sm font-medium text-warning-700 dark:bg-warning-500/10">
                        Votre période d’essai expire bientôt.
                    </p>
                @endif
            </div>
        @else
            <p class="text-sm text-gray-500">Aucun abonnement actif configure.</p>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
