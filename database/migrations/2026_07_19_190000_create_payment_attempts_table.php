<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->char('public_id', 26)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('provider', 32)->index();
            $table->unsignedInteger('attempt_number');
            $table->string('idempotency_key', 120);
            $table->char('request_hash', 64);
            $table->string('status', 32)->index();
            $table->unsignedBigInteger('amount_toman');
            $table->unsignedBigInteger('amount_provider');
            $table->string('currency', 8)->default('IRR');
            $table->string('authority', 128)->nullable();
            $table->string('reference_id', 128)->nullable();
            $table->string('gateway_code', 32)->nullable();
            $table->string('failure_code', 80)->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->text('redirect_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('verification_payload')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'attempt_number'], 'payment_attempt_order_number_unique');
            $table->unique(['order_id', 'idempotency_key'], 'payment_attempt_order_idempotency_unique');
            $table->unique(['provider', 'authority'], 'payment_attempt_provider_authority_unique');
            $table->index(['customer_id', 'created_at'], 'payment_attempt_customer_created_index');
            $table->index(['order_id', 'status'], 'payment_attempt_order_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_attempts');
    }
};
