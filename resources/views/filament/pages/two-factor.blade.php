<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Status Card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-center gap-4">
                @if(auth()->user()->two_factor_secret)
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-success-100 dark:bg-success-900">
                        <x-heroicon-o-shield-check class="h-6 w-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">احراز هویت دو مرحله‌ای فعال است ✅</h3>
                        <p class="text-sm text-gray-500">حساب شما با 2FA محافظت می‌شود</p>
                    </div>
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-danger-100 dark:bg-danger-900">
                        <x-heroicon-o-shield-exclamation class="h-6 w-6 text-danger-600 dark:text-danger-400"/>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">احراز هویت دو مرحله‌ای غیرفعال است ⚠️</h3>
                        <p class="text-sm text-gray-500">برای امنیت بیشتر 2FA را فعال کنید</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- QR Code --}}
        @if(auth()->user()->two_factor_secret && $this->showQr)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold mb-4">QR Code را اسکن کنید</h3>
            <div class="flex justify-center p-4 bg-white rounded-lg inline-block">
                {!! auth()->user()->twoFactorQrCodeSvg() !!}
            </div>
            <p class="text-sm text-gray-500 mt-3 text-center">با Google Authenticator یا Authy اسکن کنید</p>
        </div>
        @endif

        {{-- Recovery Codes --}}
        @if(auth()->user()->two_factor_secret)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold mb-4">کدهای بازیابی</h3>
            @if($this->showRecoveryCodes)
            <div class="grid grid-cols-2 gap-2 font-mono text-sm bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                @foreach(auth()->user()->recoveryCodes() as $code)
                    <div class="text-gray-700 dark:text-gray-300">{{ $code }}</div>
                @endforeach
            </div>
            <p class="text-sm text-danger-600 mt-2">این کدها را در جای امنی ذخیره کنید!</p>
            @endif
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex gap-3">
            @if(!auth()->user()->two_factor_secret)
                {{ $this->enableAction }}
            @else
                {{ $this->disableAction }}
                {{ $this->regenerateCodesAction }}
            @endif
        </div>

    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
