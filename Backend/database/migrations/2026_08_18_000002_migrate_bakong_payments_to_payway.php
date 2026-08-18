<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bakong_payments') || Schema::hasTable('payway_payments')) {
            return;
        }

        Schema::rename('bakong_payments', 'payway_payments');
        Schema::table('payway_payments', function (Blueprint $table) {
            $table->string('md5', 32)->nullable()->change();
            $table->text('qr_payload')->nullable()->change();
            $table->mediumText('qr_image')->nullable()->after('qr_payload');
            $table->text('deeplink')->nullable()->after('qr_image');
            $table->string('approval_code')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('payway_payments') && ! Schema::hasTable('bakong_payments')) {
            Schema::rename('payway_payments', 'bakong_payments');
        }
    }
};
