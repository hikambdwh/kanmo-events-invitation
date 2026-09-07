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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('guest_number');

            $table->string('guest_code', 30);

            $table->string('qr_token', 64)
                ->unique();

            $table->timestamp('checked_in_at')
                ->nullable();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'event_id',
                'guest_number',
            ]);

            $table->unique([
                'event_id',
                'guest_code',
            ]);

            $table->index([
                'event_id',
                'checked_in_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
