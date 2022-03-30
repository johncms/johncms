<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateForumTopicTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create('forum_topic', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->unsigned()->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->integer('view_count')->nullable();
            $table->integer('user_id')->unsigned();
            $table->string('user_name')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->integer('post_count')->nullable();
            $table->integer('mod_post_count')->nullable();
            $table->integer('last_post_date')->nullable();
            $table->integer('last_post_author')->unsigned()->nullable();
            $table->string('last_post_author_name')->nullable();
            $table->bigInteger('last_message_id')->nullable();
            $table->integer('mod_last_post_date')->nullable();
            $table->integer('mod_last_post_author')->unsigned()->nullable();
            $table->string('mod_last_post_author_name')->nullable();
            $table->bigInteger('mod_last_message_id')->nullable();
            $table->boolean('closed')->nullable();
            $table->string('closed_by')->nullable();
            $table->boolean('deleted')->nullable()->index('deleted');
            $table->string('deleted_by')->nullable();
            $table->mediumText('curators')->nullable();
            $table->boolean('pinned')->nullable();
            $table->boolean('has_poll')->nullable();
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
        $schema->dropIfExists('forum_topic');
    }
}
