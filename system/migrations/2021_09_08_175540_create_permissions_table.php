<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create(
            'permissions',
            function (Blueprint $table) {
                $table->integerIncrements('id');
                $table->string('name')->unique();
                $table->string('display_name');
                $table->string('description')->nullable();
                $table->string('module_name')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Capsule::schema();
        $schema->dropIfExists('permissions');
    }
}
