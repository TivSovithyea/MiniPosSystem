<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bakong_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('md5', 32)->unique();
            $table->text('qr_payload');
            $table->string('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamps();
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bakong_payments');
    }
};
