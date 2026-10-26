<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = [
        'key',
        'channel',
        'provider_template',
        'body',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'is_active' => 'boolean',
        ];
    }

    public function render(array $payload): string
    {
        $message = $this->body;

        foreach ($payload as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $message = str_replace('{{'.$key.'}}', (string) $value, $message);
            }
        }

        return $message;
    }
}
