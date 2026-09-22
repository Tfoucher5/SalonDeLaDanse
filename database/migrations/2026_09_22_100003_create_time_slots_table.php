<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->time('starts_at');
            $table->time('ends_at');
            // position porte l ordre de la tranche dans la journee : il sert au
            // calcul de la regle des trois tranches consecutives.
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['edition_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
