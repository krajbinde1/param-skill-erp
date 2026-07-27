<x-filament-widgets::widget>
    <x-filament::section>
        @if ($missingCentre)
            <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                Configuration warning: your Centre Manager account is not linked to a centre.
            </div>
        @else
            <div class="space-y-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ $centre->centre_name }}</h2>
                    <p class="text-sm text-gray-500">{{ $centre->centre_code }} · {{ $centre->status->label() }}</p>
                </div>
                <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Capacity</dt>
                        <dd class="font-medium">{{ $centre->centre_capacity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Hostel</dt>
                        <dd class="font-medium">{{ $centre->hostel_available ? 'Available' : 'Not available' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">District</dt>
                        <dd class="font-medium">{{ $centre->district }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-gray-500">Opening Date</dt>
                        <dd class="font-medium">{{ $openingDate ?? '—' }}</dd>
                    </div>
                </dl>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button tag="a" href="{{ \App\Filament\Resources\Employees\EmployeeResource::getUrl('create') }}">
                        Add Employee
                    </x-filament::button>
                    <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Resources\Employees\EmployeeResource::getUrl('index') }}">
                        View Employees
                    </x-filament::button>
                    <x-filament::button tag="a" color="gray" href="{{ \App\Filament\Pages\CentreProfile::getUrl() }}">
                        View Centre Profile
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
