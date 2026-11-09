<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id('borrowing_id');
            $table->foreignId('book_id')
                ->constrained('books', 'book_id')
                ->restrictOnDelete();
            $table->foreignId('member_id')
                ->constrained('members', 'member_id')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->constrained('users', 'user_id')
                ->restrictOnDelete();
            $table->date('borrowing_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('borrowed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};