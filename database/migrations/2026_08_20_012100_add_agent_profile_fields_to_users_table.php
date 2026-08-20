<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('office')->nullable()->after('phone');
            $table->string('license_number')->nullable()->after('office');
            $table->string('photo_url')->nullable()->after('license_number');
            $table->string('timezone')->default('America/Santo_Domingo')->after('photo_url');
            $table->string('role')->default('agent')->after('timezone');
            $table->text('bio')->nullable()->after('role');
            $table->string('fcm_token')->nullable()->after('bio');
            $table->boolean('google_calendar_linked')->default(false)->after('fcm_token');
            $table->string('google_calendar_id')->default('primary')->after('google_calendar_linked');
            $table->text('google_access_token')->nullable()->after('google_calendar_id');
            $table->text('google_refresh_token')->nullable()->after('google_access_token');
            $table->timestamp('google_token_expires_at')->nullable()->after('google_refresh_token');
            $table->string('google_email')->nullable()->after('google_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'office',
                'license_number',
                'photo_url',
                'timezone',
                'role',
                'bio',
                'fcm_token',
                'google_calendar_linked',
                'google_calendar_id',
                'google_access_token',
                'google_refresh_token',
                'google_token_expires_at',
                'google_email',
            ]);
        });
    }
};
