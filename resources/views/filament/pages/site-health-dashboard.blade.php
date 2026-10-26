<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header Actions --}}
        <div class="flex gap-3 justify-end">
            <x-filament::button wire:click="refreshChecks" color="gray" icon="heroicon-o-arrow-path">
                بروزرسانی
            </x-filament::button>
            <x-filament::button wire:click="clearCache" color="warning" icon="heroicon-o-trash">
                پاک کردن کش
            </x-filament::button>
        </div>

        {{-- Health Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($checks as $check)
                <div class="rounded-xl border p-4 shadow-sm flex items-start gap-4
                    {{ $check['status'] === 'ok' ? 'bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800' : '' }}
                    {{ $check['status'] === 'warning' ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950 dark:border-yellow-800' : '' }}
                    {{ $check['status'] === 'error' ? 'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800' : '' }}
                ">
                    <div class="mt-1">
                        @if($check['status'] === 'ok')
                            <x-heroicon-o-check-circle class="w-6 h-6 text-green-500" />
                        @elseif($check['status'] === 'warning')
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-yellow-500" />
                        @else
                            <x-heroicon-o-x-circle class="w-6 h-6 text-red-500" />
                        @endif
                    </div>
                    <div>
                        <div class="font-semibold text-sm text-gray-700 dark:text-gray-200">
                            {{ $check['label'] }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $check['value'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- System Info --}}
        <div class="rounded-xl border p-5 shadow-sm bg-white dark:bg-gray-900">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-4">اطلاعات سیستم</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <div class="text-gray-400 text-xs">PHP Version</div>
                    <div class="font-medium">{{ PHP_VERSION }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">Laravel Version</div>
                    <div class="font-medium">{{ app()->version() }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">محیط</div>
                    <div class="font-medium">{{ app()->environment() }}</div>
                </div>
                <div>
                    <div class="text-gray-400 text-xs">Timezone</div>
                    <div class="font-medium">{{ config('app.timezone') }}</div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
