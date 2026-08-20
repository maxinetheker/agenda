<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('seguimiento');
            $table->dateTime('due_at');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('completed')->default(false);
            $table->boolean('synced_to_google')->default(false);
            $table->string('google_event_id')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
