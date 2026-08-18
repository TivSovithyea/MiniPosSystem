<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payway_payments', function (Blueprint $table) {
            $table->mediumText('qr_image')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payway_payments', function (Blueprint $table) {
            $table->text('qr_image')->nullable()->change();
        });
    }
};
