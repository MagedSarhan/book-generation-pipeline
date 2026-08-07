<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('master_prompt')->nullable();
            $table->string('source_type')->default('images'); // pdf, docx, images
            $table->string('source_file_path')->nullable();
            $table->integer('default_batch_size')->default(10);
            $table->string('default_quality')->default('high'); // low, medium, high
            $table->string('default_resolution')->default('auto'); // auto, a4_draft, a4_high, a4_max, custom
            $table->integer('custom_width')->nullable();
            $table->integer('custom_height')->nullable();
            $table->string('default_output_format')->default('png'); // png, jpeg, webp
            $table->integer('default_variants')->default(1);
            $table->boolean('continuation_mode')->default(true);
            $table->string('status')->default('active'); // active, archived
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
