<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumVoteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_vote', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type')->default(0)->index('type');
            $table->integer('time')->unsigned()->default(0);
            $table->integer('topic')->unsigned()->default(0)->index('topic');
            $table->string('name');
            $table->integer('count')->unsigned()->default(0);
            $table->index(['type', 'topic'], 'type_topic');
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
        $schema->dropIfExists('forum_vote');
    }
}
