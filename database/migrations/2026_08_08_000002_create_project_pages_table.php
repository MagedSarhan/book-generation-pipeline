<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->integer('page_number');
            $table->string('source_image_path');
            $table->string('thumbnail_path')->nullable();
            $table->longText('extracted_text')->nullable();
            $table->unsignedBigInteger('selected_version_id')->nullable();
            $table->string('status')->default('imported'); 
            // imported, pending, queued, submitted, generating, completed, failed, cancelled
            $table->timestamps();

            $table->unique(['project_id', 'page_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_pages');
    }
};
