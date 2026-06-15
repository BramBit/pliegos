<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('process_id')->unique();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('entity')->nullable();
            $table->string('city')->nullable();
            $table->string('department')->nullable();
            $table->unsignedBigInteger('budget')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('url')->nullable();
            $table->string('sector')->nullable();
            $table->boolean('indexed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenders');
    }
};
