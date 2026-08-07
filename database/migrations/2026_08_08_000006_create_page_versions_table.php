<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_page_id')->constrained('project_pages')->cascadeOnDelete();
            $table->foreignId('generation_job_id')->nullable()->constrained('generation_jobs')->nullOnDelete();
            $table->integer('version_number');
            $table->string('image_path');
            $table->string('provider_url')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('content_type')->default('image/png');
            $table->boolean('is_selected')->default(false);
            $table->text('user_instruction')->nullable();
            $table->timestamps();

            $table->unique(['project_page_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_versions');
    }
};
