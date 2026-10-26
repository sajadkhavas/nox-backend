<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['label' => 'PHP', 'value' => $stats['php'], 'icon' => 'heroicon-o-code-bracket'],
                ['label' => 'Laravel', 'value' => $stats['laravel'], 'icon' => 'heroicon-o-cube'],
                ['label' => 'Cache Driver', 'value' => $stats['cache'], 'icon' => 'heroicon-o-bolt'],
                ['label' => 'Memory', 'value' => $stats['memory'], 'icon' => 'heroicon-o-cpu-chip'],
                ['label' => 'دیسک آزاد', 'value' => $stats['disk_free'], 'icon' => 'heroicon-o-server'],
                ['label' => 'دیسک کل', 'value' => $stats['disk_total'], 'icon' => 'heroicon-o-server-stack'],
                ['label' => 'محیط', 'value' => $stats['env'], 'icon' => 'heroicon-o-cog-6-tooth'],
            ] as $stat)
            <div class="rounded-xl border p-4 bg-white dark:bg-gray-900 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">{{ $stat['label'] }}</div>
                <div class="font-semibold text-gray-700 dark:text-gray-200">{{ $stat['value'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Actions --}}
        <div class="rounded-xl border p-5 bg-white dark:bg-gray-900 shadow-sm space-y-4">
            <h3 class="font-bold text-gray-700 dark:text-gray-200">عملیات Cache</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Clear Actions --}}
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">پاک کردن</p>
                    <x-filament::button wire:click="clearAll" color="danger" icon="heroicon-o-trash" class="w-full">
                        پاک کردن همه Cache ها
                    </x-filament::button>
                    <x-filament::button wire:click="clearCache" color="warning" icon="heroicon-o-bolt" class="w-full">
                        Application Cache
                    </x-filament::button>
                    <x-filament::button wire:click="clearConfig" color="warning" icon="heroicon-o-cog-6-tooth" class="w-full">
                        Config Cache
                    </x-filament::button>
                    <x-filament::button wire:click="clearRoutes" color="warning" icon="heroicon-o-map" class="w-full">
                        Route Cache
                    </x-filament::button>
                    <x-filament::button wire:click="clearViews" color="warning" icon="heroicon-o-eye" class="w-full">
                        View Cache
                    </x-filament::button>
                    <x-filament::button wire:click="clearEvents" color="warning" icon="heroicon-o-calendar" class="w-full">
                        Event Cache
                    </x-filament::button>
                </div>

                {{-- Cache Actions --}}
                <div class="space-y-2">
                    <p class="text-xs font-semibold text-green-500 uppercase tracking-wide">ساخت Cache</p>
                    <x-filament::button wire:click="cacheConfig" color="success" icon="heroicon-o-cog-6-tooth" class="w-full">
                        Cache Config
                    </x-filament::button>
                    <x-filament::button wire:click="cacheRoutes" color="success" icon="heroicon-o-map" class="w-full">
                        Cache Routes
                    </x-filament::button>
                    <x-filament::button wire:click="cacheViews" color="success" icon="heroicon-o-eye" class="w-full">
                        Cache Views
                    </x-filament::button>
                </div>
            </div>
        </div>

        {{-- Results Log --}}
        @if(count($results) > 0)
        <div class="rounded-xl border p-5 bg-white dark:bg-gray-900 shadow-sm">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-bold text-gray-700 dark:text-gray-200">نتایج</h3>
                <x-filament::button wire:click="clearResults" color="gray" size="sm">
                    پاک کردن لاگ
                </x-filament::button>
            </div>
            <div class="space-y-2">
                @foreach(array_reverse($results) as $result)
                <div class="flex items-center gap-3 text-sm p-2 rounded-lg
                    {{ $result['type'] === 'success' ? 'bg-green-50 dark:bg-green-950' : 'bg-blue-50 dark:bg-blue-950' }}">
                    <span class="text-gray-400 text-xs font-mono">{{ $result['time'] }}</span>
                    <span class="{{ $result['type'] === 'success' ? 'text-green-700 dark:text-green-300' : 'text-blue-700 dark:text-blue-300' }}">
                        {{ $result['msg'] }}
                    </span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-filament-panels::page>
