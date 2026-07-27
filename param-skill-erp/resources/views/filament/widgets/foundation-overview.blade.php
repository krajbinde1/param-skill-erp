<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div>
                <h2 class="text-xl font-semibold tracking-tight text-gray-950 dark:text-white">
                    {{ $appName }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Administration console foundation
                </p>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Logged-in user
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                        {{ $userName }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Role
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                        {{ $userRole }}
                    </dd>
                </div>
            </dl>

            @if ($showEnvironmentWarning)
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                    Environment warning: this application is running in
                    <strong>{{ $environment }}</strong>
                    mode. Production-only behaviour is not active.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
