<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('tender_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE tender_embeddings ADD COLUMN embedding vector(768)');
        DB::statement('CREATE INDEX tender_embeddings_embedding_idx ON tender_embeddings USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_embeddings');
    }
};
