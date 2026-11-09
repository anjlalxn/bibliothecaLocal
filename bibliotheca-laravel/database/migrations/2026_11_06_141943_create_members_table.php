<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id('member_id');
            $table->string('member_code')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};