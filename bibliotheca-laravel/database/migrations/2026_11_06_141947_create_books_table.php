<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id('book_id');
            $table->foreignId('category_id')
                ->constrained('categories', 'category_id')
                ->restrictOnDelete();
            $table->string('title');
            $table->string('author');
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('available_quantity')->default(1);
            $table->string('status')->default('available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};