<?php

namespace App\Services\Auth;

use App\Exceptions\OtpDeliveryUnavailable;
use Illuminate\Support\Facades\Http;
use Throwable;

final class OtpSender
{
    public function send(string $mobile, string $code): void
    {
        match ((string) config('lbb.otp.provider', 'disabled')) {
            'testing' => $this->sendForTesting(),
            'kavenegar' => $this->sendWithKavenegar($mobile, $code),
            default => throw new OtpDeliveryUnavailable,
        };
    }

    private function sendForTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new OtpDeliveryUnavailable;
        }
    }

    private function sendWithKavenegar(string $mobile, string $code): void
    {
        $apiKey = trim((string) config('lbb.otp.kavenegar.api_key'));
        $template = trim((string) config('lbb.otp.kavenegar.template'));
        $baseUrl = rtrim((string) config('lbb.otp.kavenegar.base_url'), '/');

        if ($apiKey === '' || $template === '') {
            throw new OtpDeliveryUnavailable;
        }

        try {
            Http::acceptJson()
                ->timeout(8)
                ->retry(2, 250, throw: false)
                ->get("{$baseUrl}/{$apiKey}/verify/lookup.json", [
                    'receptor' => $mobile,
                    'token' => $code,
                    'template' => $template,
                ])
                ->throw();
        } catch (Throwable) {
            throw new OtpDeliveryUnavailable;
        }
    }
}
