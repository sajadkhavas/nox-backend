<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-xl border p-5 bg-white dark:bg-gray-900 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">درایور</div>
                <div class="text-2xl font-bold text-gray-700 dark:text-gray-200">{{ $stats['driver'] }}</div>
            </div>
            <div class="rounded-xl border p-5 shadow-sm
                {{ $stats['pending'] > 0 ? 'bg-yellow-50 border-yellow-200 dark:bg-yellow-950' : 'bg-green-50 border-green-200 dark:bg-green-950' }}">
                <div class="text-xs text-gray-400 mb-1">Jobs در انتظار</div>
                <div class="text-2xl font-bold {{ $stats['pending'] > 0 ? 'text-yellow-600' : 'text-green-600' }}">
                    {{ $stats['pending'] }}
                </div>
            </div>
            <div class="rounded-xl border p-5 shadow-sm
                {{ $stats['failed'] > 0 ? 'bg-red-50 border-red-200 dark:bg-red-950' : 'bg-green-50 border-green-200 dark:bg-green-950' }}">
                <div class="text-xs text-gray-400 mb-1">Failed Jobs</div>
                <div class="text-2xl font-bold {{ $stats['failed'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $stats['failed'] }}
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <x-filament::button wire:click="retryAll" color="warning" icon="heroicon-o-arrow-path">
                Retry همه Failed Jobs
            </x-filament::button>
            <x-filament::button wire:click="flushFailed" color="danger" icon="heroicon-o-trash">
                پاک کردن همه Failed Jobs
            </x-filament::button>
        </div>

        {{-- Failed Jobs Table --}}
        <div class="rounded-xl border bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="p-4 border-b">
                <h3 class="font-bold text-gray-700 dark:text-gray-200">Failed Jobs</h3>
            </div>
            @if(count($failedJobs) > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="p-3 text-right text-gray-500">#</th>
                        <th class="p-3 text-right text-gray-500">Job</th>
                        <th class="p-3 text-right text-gray-500">Queue</th>
                        <th class="p-3 text-right text-gray-500">خطا</th>
                        <th class="p-3 text-right text-gray-500">زمان</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($failedJobs as $job)
                    <tr class="border-t hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="p-3 text-gray-500">{{ $job['id'] }}</td>
                        <td class="p-3 font-medium">{{ $job['payload'] }}</td>
                        <td class="p-3"><span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">{{ $job['queue'] }}</span></td>
                        <td class="p-3 text-red-500 text-xs">{{ $job['exception'] }}</td>
                        <td class="p-3 text-gray-400 text-xs">{{ $job['failed_at'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div class="p-8 text-center text-gray-400">
                <x-heroicon-o-check-circle class="w-10 h-10 mx-auto mb-2 text-green-400" />
                هیچ failed job ای وجود ندارد
            </div>
            @endif
        </div>

        {{-- Info --}}
        <div class="rounded-xl border p-4 bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800 text-sm text-blue-600 dark:text-blue-400">
            برای اجرای queue worker بزن: <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded font-mono">php artisan queue:work</code>
        </div>

    </div>
</x-filament-panels::page>
