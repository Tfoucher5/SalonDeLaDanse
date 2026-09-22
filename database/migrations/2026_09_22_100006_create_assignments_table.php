<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            // Attribution forcee par un administrateur, regles metier court-circuitees.
            $table->boolean('assigned_by_admin')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'shift_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
