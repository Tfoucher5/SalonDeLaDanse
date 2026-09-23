<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            // Une mission desactivee n'est plus proposee au benevole, mais ce
            // qui lui est deja attribue reste en place. C'est distinct de
            // `is_public`, qui dit qui a le droit de la reserver.
            $table->boolean('is_active')->default(true)->after('is_public');

            // La jauge reste portee par `shifts`, parametrable creneau par
            // creneau. Cette colonne est la valeur de reference de la mission :
            // celle qu'on applique a tous ses creneaux depuis le back-office,
            // et celle que recoivent ses nouveaux creneaux.
            $table->unsignedSmallInteger('default_capacity')->default(4)->after('is_active');
        });

        // Les missions existantes heritent de la jauge de leurs creneaux, pour
        // que la valeur affichee au back-office corresponde a la base.
        foreach (DB::table('missions')->select('id')->get() as $mission) {
            $capacity = DB::table('shifts')->where('mission_id', $mission->id)->max('capacity');

            if ($capacity !== null) {
                DB::table('missions')->where('id', $mission->id)->update(['default_capacity' => $capacity]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('missions', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'default_capacity']);
        });
    }
};
