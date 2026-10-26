<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Info --}}
        <div class="rounded-xl border p-4 bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800 text-sm text-blue-600 dark:text-blue-400">
            فایل robots.txt به موتورهای جستجو میگه کدام صفحات رو ایندکس کنن.
            آدرس فایل: <code class="bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded font-mono">/robots.txt</code>
        </div>

        {{-- Form --}}
        <div class="rounded-xl border p-5 bg-white dark:bg-gray-900 shadow-sm">
            <form wire:submit.prevent="save">
                {{ $this->form }}
                <div class="flex gap-3 mt-4">
                    <x-filament::button type="submit" icon="heroicon-o-check">
                        ذخیره
                    </x-filament::button>
                    <x-filament::button wire:click="resetToDefault" color="gray" icon="heroicon-o-arrow-path" type="button">
                        برگشت به پیش‌فرض
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        href="/robots.txt"
                        target="_blank"
                        color="gray"
                        icon="heroicon-o-eye">
                        مشاهده
                    </x-filament::button>
                </div>
            </form>
        </div>

    </div>
</x-filament-panels::page>
