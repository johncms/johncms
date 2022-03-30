<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_files', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cat')->unsigned()->default(0)->index('cat');
            $table->integer('subcat')->unsigned()->default(0)->index('subcat');
            $table->integer('topic')->unsigned()->default(0)->index('topic');
            $table->integer('post')->unsigned()->default(0)->index('post');
            $table->integer('time')->unsigned()->default(0);
            $table->text('filename');
            $table->tinyInteger('filetype')->unsigned()->default(0);
            $table->integer('dlcount')->unsigned()->default(0);
            $table->boolean('del')->default(0);
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
        $schema->dropIfExists('forum_files');
    }
}
