<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGuestSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('guest_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->timestamps();
            $table->string('ip')->nullable();
            $table->string('ip_via_proxy')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('route')->index()->nullable();
            $table->json('route_params')->nullable();
            $table->integer('movements')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Capsule::schema();
        $schema->dropIfExists('guest_sessions');
    }
}
