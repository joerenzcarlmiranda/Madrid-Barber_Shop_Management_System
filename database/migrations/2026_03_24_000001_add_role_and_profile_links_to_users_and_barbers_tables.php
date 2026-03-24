<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default(UserRole::ADMIN->value)->after('password');
            $table->foreignId('barber_id')->nullable()->unique()->after('role')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->unique()->after('barber_id')->constrained()->cascadeOnDelete();
        });

        DB::table('users')->whereNull('role')->update([
            'role' => UserRole::ADMIN->value,
        ]);

        Schema::table('barbers', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('lastname');
        });
    }

    public function down(): void
    {
        Schema::table('barbers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('barber_id');
            $table->dropColumn('role');
        });
    }
};
