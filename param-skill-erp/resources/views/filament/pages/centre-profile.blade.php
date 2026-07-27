<x-filament-panels::page>
    @if (! $centre)
        <x-filament::section>
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                Your account is not linked to a centre. Please contact the administrator.
            </div>
        </x-filament::section>
    @else
        <div class="space-y-6">
            <x-filament::section>
                <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Centre Code</dt>
                        <dd class="font-medium">{{ $centre->centre_code }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Centre Name</dt>
                        <dd class="font-medium">{{ $centre->centre_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Status</dt>
                        <dd class="font-medium">{{ $centre->status->label() }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Capacity</dt>
                        <dd class="font-medium">{{ $centre->centre_capacity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Hostel</dt>
                        <dd class="font-medium">{{ $centre->hostel_available ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Opening Date</dt>
                        <dd class="font-medium">{{ \App\Support\Format::date($centre->opening_date) ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($centre->agreement_document)
                    <div class="mt-4">
                        <x-filament::button wire:click="downloadAgreement" color="gray">
                            Download Agreement
                        </x-filament::button>
                    </div>
                @endif
            </x-filament::section>

            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}
                <x-filament::button type="submit">Save changes</x-filament::button>
            </form>
        </div>
    @endif
</x-filament-panels::page>
