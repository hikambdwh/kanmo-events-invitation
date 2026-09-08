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
        Schema::create('exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('type', 30);
            $table->string('status', 20)
                ->default('processing');

            $table->unsignedInteger('total_items')
                ->default(0);

            $table->unsignedInteger('processed_items')
                ->default(0);

            // Snapshot guest terakhir ketika export dimulai.
            $table->unsignedInteger('max_guest_number')
                ->nullable();

            // Guest terakhir yang sudah masuk ZIP.
            $table->unsignedInteger('last_guest_number')
                ->nullable();

            $table->string('file_name')
                ->nullable();

            $table->string('file_path')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'event_id',
                'type',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
