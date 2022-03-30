<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumReadTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_read', function (Blueprint $table) {
            $table->integer('topic_id')->unsigned()->default(0);
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0)->index('time');
            $table->primary(['topic_id', 'user_id'], 'topic_user');
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
        $schema->dropIfExists('forum_read');
    }
}
