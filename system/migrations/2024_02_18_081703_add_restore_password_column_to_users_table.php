<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $schema = Capsule::schema();
        $schema->table('users', function (Blueprint $table) {
            $table->string('restore_password_code')->nullable();
            $table->dateTime('restore_password_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $schema = Capsule::schema();
        $schema->table('users', function (Blueprint $table) {
            $table->dropColumn(['restore_password_code', 'restore_password_date']);
        });
    }
};
