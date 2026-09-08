<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_designs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('background_path')
                ->nullable();

            $table->unsignedInteger('canvas_width')
                ->nullable();

            $table->unsignedInteger('canvas_height')
                ->nullable();

            $table->json('design_data')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_designs');
    }
};
