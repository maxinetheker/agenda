<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('type')->default('lead');
            $table->string('stage')->default('nuevo');
            $table->text('notes')->nullable();
            $table->string('address')->nullable();
            $table->string('property_interest')->nullable();
            $table->string('budget')->nullable();
            $table->string('source')->default('manual');
            $table->string('device_contact_id')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
