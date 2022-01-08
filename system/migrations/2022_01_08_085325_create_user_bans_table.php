<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserBansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('user_bans', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('active_from');
            $table->timestamp('active_to');
            $table->foreignId('user_id');
            $table->string('type');
            $table->foreignId('banned_by_id')->nullable();
            $table->string('reason')->nullable();
            $table->json('additional_fields')->nullable();
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
        $schema->dropIfExists('user_bans');
    }
}
