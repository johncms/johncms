<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumVoteUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_vote_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user')->default(0);
            $table->integer('topic')->index('topic');
            $table->integer('vote');
            $table->index(['topic', 'user'], 'topic_user');
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
        $schema->dropIfExists('forum_vote_users');
    }
}
