<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('image')->nullable()->after('phone_no');
        });

        Schema::table('barbers', function (Blueprint $table) {
            $table->string('image')->nullable()->after('contact_number');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('image')->nullable()->after('duration');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('barbers', function (Blueprint $table) {
            $table->dropColumn('image');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
