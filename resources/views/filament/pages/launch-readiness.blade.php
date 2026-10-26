<x-filament-panels::page>
    <div class="space-y-6" dir="rtl">
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">وضعیت آماده‌سازی تحویل LBB</h2>
                    <p class="mt-1 text-sm text-gray-500">این صفحه فقط وضعیت را می‌خواند؛ هیچ Checkout، Payment یا داده تجاری را فعال نمی‌کند.</p>
                </div>
                <x-filament::button wire:click="refreshChecks" icon="heroicon-o-arrow-path">
                    بروزرسانی وضعیت
                </x-filament::button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @foreach ($checks as $check)
                <div class="rounded-xl border p-5 shadow-sm {{ $check['status'] === 'ok' ? 'border-success-300 bg-success-50/40 dark:border-success-700 dark:bg-success-950/20' : 'border-warning-300 bg-warning-50/40 dark:border-warning-700 dark:bg-warning-950/20' }}">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-bold">{{ $check['label'] }}</h3>
                            <p class="mt-2 text-sm font-medium">{{ $check['value'] }}</p>
                            <p class="mt-2 text-xs leading-6 text-gray-500">{{ $check['note'] }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $check['status'] === 'ok' ? 'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-300' : 'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-300' }}">
                            {{ $check['status'] === 'ok' ? 'OK' : 'نیازمند تکمیل' }}
                        </span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
