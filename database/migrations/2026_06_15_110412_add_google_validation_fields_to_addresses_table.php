<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->enum('validation_status', ['valid', 'invalid', 'corrected'])->default('invalid')->change();
            $table->text('validation_message')->nullable()->after('validation_errors');
            $table->string('corrected_address_1')->nullable()->after('validation_message');
            $table->string('corrected_address_2')->nullable()->after('corrected_address_1');
            $table->string('corrected_suburb')->nullable()->after('corrected_address_2');
            $table->string('corrected_state')->nullable()->after('corrected_suburb');
            $table->string('corrected_postcode')->nullable()->after('corrected_state');
            $table->json('google_api_response')->nullable()->after('corrected_postcode');
            $table->boolean('is_google_verified')->default(false)->after('google_api_response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn([
                'validation_message',
                'corrected_address_1',
                'corrected_address_2',
                'corrected_suburb',
                'corrected_state',
                'corrected_postcode',
                'google_api_response',
                'is_google_verified'
            ]);
            $table->enum('validation_status', ['valid', 'invalid'])->default('invalid')->change();
        });
    }
};
