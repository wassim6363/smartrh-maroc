<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-3">
        <x-filament::section class="sr-payroll-preview xl:col-span-2">
            <x-slot name="heading">Prévisualisation du bulletin de paie</x-slot>
            <x-slot name="description">Calculez les rubriques, vérifiez les bases fiscales puis générez le bulletin PDF.</x-slot>

            <div class="grid gap-4 md:grid-cols-3">
                <label class="sr-payroll-field grid gap-1 text-sm font-medium">
                    <span>Société</span>
                    <select wire:model.live="company_id" class="fi-input sr-payroll-input block w-full rounded-lg">
                        @foreach ($this->companies() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="sr-payroll-field grid gap-1 text-sm font-medium">
                    <span>Salarié</span>
                    <select wire:model.live="employee_id" class="fi-input sr-payroll-input block w-full rounded-lg">
                        @foreach ($this->employees() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="sr-payroll-field grid gap-1 text-sm font-medium">
                    <span>Période de paie</span>
                    <select wire:model.live="payroll_period_id" class="fi-input sr-payroll-input block w-full rounded-lg">
                        @foreach ($this->periods() as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <div class="sr-payroll-table-wrap mt-6 overflow-x-auto rounded-lg">
                <table class="sr-payroll-items-table w-full min-w-[900px] text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="px-3 py-2">Code</th>
                            <th class="px-3 py-2">Libellé</th>
                            <th class="px-3 py-2">Type</th>
                            <th class="px-3 py-2 text-right">Montant</th>
                            <th class="px-3 py-2 text-center">CNSS</th>
                            <th class="px-3 py-2 text-center">AMO</th>
                            <th class="px-3 py-2 text-center">IR</th>
                            <th class="px-3 py-2 text-center">Exonérée</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $index => $item)
                            <tr wire:key="payroll-item-{{ $index }}">
                                <td class="px-3 py-2"><input wire:model="items.{{ $index }}.code" class="fi-input sr-payroll-input w-28 rounded-lg"></td>
                                <td class="px-3 py-2"><input wire:model="items.{{ $index }}.label" class="fi-input sr-payroll-input w-56 rounded-lg"></td>
                                <td class="px-3 py-2">
                                    <select wire:model="items.{{ $index }}.type" class="fi-input sr-payroll-input rounded-lg">
                                        <option value="earning">Gain</option>
                                        <option value="deduction">Retenue</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2"><input wire:model="items.{{ $index }}.amount" type="number" step="0.01" class="fi-input sr-payroll-input w-32 rounded-lg text-right"></td>
                                <td class="px-3 py-2 text-center"><input wire:model="items.{{ $index }}.subject_to_cnss" type="checkbox" class="sr-payroll-checkbox"></td>
                                <td class="px-3 py-2 text-center"><input wire:model="items.{{ $index }}.subject_to_amo" type="checkbox" class="sr-payroll-checkbox"></td>
                                <td class="px-3 py-2 text-center"><input wire:model="items.{{ $index }}.subject_to_ir" type="checkbox" class="sr-payroll-checkbox"></td>
                                <td class="px-3 py-2 text-center"><input wire:model="items.{{ $index }}.is_tax_exempt" type="checkbox" class="sr-payroll-checkbox"></td>
                                <td class="px-3 py-2 text-right">
                                    <x-filament::icon-button icon="heroicon-m-trash" color="danger" wire:click="removeItem({{ $index }})" label="Supprimer" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button icon="heroicon-m-plus" color="gray" wire:click="addItem">Ajouter une rubrique</x-filament::button>
                <x-filament::button icon="heroicon-m-calculator" wire:click="calculatePreview">Prévisualiser</x-filament::button>
                <x-filament::button icon="heroicon-m-document-check" color="success" wire:click="generatePayslip">Générer le bulletin</x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section class="sr-payroll-result">
            <x-slot name="heading">Résultat</x-slot>
            @if ($preview)
                <dl class="sr-payroll-result-list space-y-3 text-sm">
                    @foreach ([
                        'Salaire brut' => 'gross_total',
                        'Brut imposable' => 'taxable_gross',
                        'Base CNSS' => 'cnss_base',
                        'Base AMO' => 'amo_base',
                        'CNSS salarié' => 'cnss_employee',
                        'AMO salarié' => 'amo_employee',
                        'Frais professionnels' => 'professional_expenses',
                        'Revenu net imposable' => 'taxable_net_income',
                        'IR' => 'ir_net',
                        'Indemnités exonérées' => 'exempt_allowances',
                    ] as $label => $key)
                        <div class="flex justify-between gap-4">
                            <dt>{{ $label }}</dt>
                            <dd class="font-semibold">{{ $this->formatMoney($preview[$key] ?? 0) }}</dd>
                        </div>
                    @endforeach
                    <div class="sr-payroll-net mt-4 rounded-lg p-4">
                        <div class="text-sm font-medium">Net à payer</div>
                        <div class="text-2xl font-bold">{{ $this->formatMoney($preview['net_to_pay'] ?? 0) }}</div>
                    </div>
                </dl>
            @else
                <p class="sr-payroll-empty text-sm">Lancez une prévisualisation pour afficher le détail du calcul.</p>
            @endif

            @if ($generatedPayslipId)
                <div class="sr-payroll-generated mt-4 rounded-lg p-3 text-sm">
                    Bulletin #{{ $generatedPayslipId }} généré.<br>
                    PDF: {{ $generatedPdfPath }}
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
