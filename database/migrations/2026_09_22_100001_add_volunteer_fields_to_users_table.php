<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Le cahier des charges demande un prenom et un nom separes :
            // la colonne "name" livree par Breeze est remplacee.
            $table->string('first_name')->default('')->after('id');
            $table->string('last_name')->default('')->after('first_name');
            $table->string('phone')->default('')->after('email');
            $table->string('photo_path')->nullable()->after('phone');
            $table->string('role')->default('volunteer')->index();
            $table->dateTime('profile_locked_at')->nullable();
            $table->dateTime('planning_validated_at')->nullable();
            $table->foreignId('edition_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->default('');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('edition_id');
            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'photo_path',
                'role',
                'profile_locked_at',
                'planning_validated_at',
            ]);
        });
    }
};
