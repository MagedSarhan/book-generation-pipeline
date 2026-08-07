<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generation_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->integer('total_jobs')->default(0);
            $table->integer('completed_jobs')->default(0);
            $table->integer('failed_jobs')->default(0);
            $table->integer('pending_jobs')->default(0);
            $table->string('status')->default('active'); // active, paused, completed, cancelled
            $table->text('instruction')->nullable();
            $table->timestamps();
        });

        Schema::create('generation_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_page_id')->constrained('project_pages')->cascadeOnDelete();
            $table->foreignId('generation_batch_id')->nullable()->constrained('generation_batches')->nullOnDelete();
            $table->string('fal_request_id')->nullable()->index();
            $table->string('provider')->default('workflow'); // workflow, direct
            $table->string('mode')->default('redesign'); // redesign, edit
            $table->string('quality')->default('high');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('output_format')->default('png');
            $table->integer('num_images')->default(1);
            $table->longText('compiled_prompt');
            $table->json('input_metadata')->nullable();
            $table->string('status')->default('pending'); 
            // pending, queued, submitted, generating, completed, failed, cancelled
            $table->integer('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->json('raw_provider_payload')->nullable();
            $table->json('raw_provider_response')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_jobs');
        Schema::dropIfExists('generation_batches');
    }
};
