<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status Card --}}
        <div class="rounded-xl border p-6 shadow-sm bg-white dark:bg-gray-900">
            <h3 class="font-bold text-gray-700 dark:text-gray-200 mb-4">وضعیت Sitemap</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="rounded-lg p-4 {{ $fileExists ? 'bg-green-50 border border-green-200 dark:bg-green-950 dark:border-green-800' : 'bg-red-50 border border-red-200 dark:bg-red-950 dark:border-red-800' }}">
                    <div class="flex items-center gap-2">
                        @if($fileExists)
                            <x-heroicon-o-check-circle class="w-5 h-5 text-green-500" />
                            <span class="text-sm font-medium text-green-700 dark:text-green-300">فایل موجود است</span>
                        @else
                            <x-heroicon-o-x-circle class="w-5 h-5 text-red-500" />
                            <span class="text-sm font-medium text-red-700 dark:text-red-300">فایل وجود ندارد</span>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg p-4 bg-gray-50 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="text-xs text-gray-400 mb-1">تعداد URL ها</div>
                    <div class="text-2xl font-bold text-gray-700 dark:text-gray-200">
                        {{ $urlCount ?? '—' }}
                    </div>
                </div>

                <div class="rounded-lg p-4 bg-gray-50 border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                    <div class="text-xs text-gray-400 mb-1">آخرین بروزرسانی</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ $lastGenerated ?? '—' }}
                    </div>
                </div>

            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <x-filament::button wire:click="generate" icon="heroicon-o-arrow-path">
                ساخت / بروزرسانی Sitemap
            </x-filament::button>

            @if($fileExists)
                <x-filament::button
                    tag="a"
                    href="/sitemap.xml"
                    target="_blank"
                    color="gray"
                    icon="heroicon-o-eye">
                    مشاهده sitemap.xml
                </x-filament::button>
            @endif
        </div>

        {{-- Info --}}
        <div class="rounded-xl border p-5 bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800">
            <h4 class="font-semibold text-blue-700 dark:text-blue-300 mb-2">راهنما</h4>
            <ul class="text-sm text-blue-600 dark:text-blue-400 space-y-1 list-disc list-inside">
                <li>بعد از هر تغییر در محصولات، دسته‌بندی‌ها یا بلاگ، Sitemap رو بروز کن</li>
                <li>آدرس Sitemap: <code class="bg-blue-100 dark:bg-blue-900 px-1 rounded">/sitemap.xml</code></li>
                <li>این آدرس رو در Google Search Console ثبت کن</li>
            </ul>
        </div>

    </div>
</x-filament-panels::page>
