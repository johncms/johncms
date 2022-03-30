<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('topic_id')->index('topic_id');
            $table->longText('text')->fulltext('text');
            $table->integer('date')->nullable();
            $table->integer('user_id')->unsigned();
            $table->string('user_name')->nullable();
            $table->string('user_agent')->nullable();
            $table->bigInteger('ip')->nullable();
            $table->bigInteger('ip_via_proxy')->nullable();
            $table->boolean('pinned')->nullable();
            $table->string('editor_name')->nullable();
            $table->integer('edit_time')->nullable();
            $table->integer('edit_count')->nullable();
            $table->boolean('deleted')->nullable()->index('deleted');
            $table->string('deleted_by')->nullable();
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
        $schema->dropIfExists('forum_messages');
    }
}
