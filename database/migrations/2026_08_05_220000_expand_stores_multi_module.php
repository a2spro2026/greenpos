<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('code');
            $table->string('region')->nullable()->after('city');
            $table->string('country')->nullable()->after('region')->default('Maroc');
            $table->string('postal_code')->nullable()->after('country');
            $table->foreignId('manager_user_id')->nullable()->after('email')->constrained('users')->nullOnDelete();
            $table->json('opening_hours')->nullable()->after('manager_user_id');
            $table->json('local_settings')->nullable()->after('opening_hours');
            $table->text('notes')->nullable()->after('local_settings');
            $table->decimal('latitude', 10, 7)->nullable()->after('notes');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('manager_user_id');
            $table->dropColumn([
                'logo_path', 'region', 'country', 'postal_code',
                'opening_hours', 'local_settings', 'notes', 'latitude', 'longitude',
            ]);
        });
    }
};
