<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFilesTable extends Migration
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
            'files',
            static function (Blueprint $table) {
                $table->id();
                $table->string('storage')->index();
                $table->string('name');
                $table->string('path');
                $table->integer('size')->unsigned()->nullable();
                $table->string('md5', 32)->nullable();
                $table->string('sha1', 40)->nullable();
                $table->timestamps();
                $table->softDeletes();
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
        $schema->drop('files');
    }
}
