<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            // is_public a false : mission sous restriction, invisible du benevole.
            $table->boolean('is_public')->default(true);
            $table->text('instructions')->nullable();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['edition_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
    }
};
