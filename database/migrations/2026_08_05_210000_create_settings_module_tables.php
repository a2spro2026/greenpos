<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('logo_path')->nullable()->after('legal_name');
            $table->string('email')->nullable()->after('logo_path');
            $table->string('phone')->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->string('address')->nullable()->after('website');
            $table->string('city')->nullable()->after('address');
            $table->string('region')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('region');
            $table->string('country')->nullable()->after('postal_code')->default('Maroc');
            $table->string('ice')->nullable()->after('country');
            $table->string('if_number')->nullable()->after('ice');
            $table->string('rc')->nullable()->after('if_number');
            $table->string('patente')->nullable()->after('rc');
            $table->string('tax_id')->nullable()->after('patente');
            $table->string('cnss')->nullable()->after('tax_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->string('address')->nullable()->after('city');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->boolean('is_default')->default(false)->after('is_active');
        });

        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('group', 64); // tax, currencies, languages, numbering, pos, invoicing, payments, notifications, security, backup, appearance, integrations
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['code', 'address', 'phone', 'email', 'is_default']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name', 'logo_path', 'email', 'phone', 'website',
                'address', 'city', 'region', 'postal_code', 'country',
                'ice', 'if_number', 'rc', 'patente', 'tax_id', 'cnss',
            ]);
        });
    }
};
