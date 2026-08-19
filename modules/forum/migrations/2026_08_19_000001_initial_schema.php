<?php

/**
 * The tables of the forum module as they stood when migrations were introduced.
 *
 * Part of the baseline, so it creates only what is missing: a site upgrading from 9.9 already has
 * them and has to arrive at the same schema as a fresh installation.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createCmsForumFiles();
        $this->createCmsForumRdm();
        $this->createCmsForumVote();
        $this->createCmsForumVoteUsers();
        $this->createForumMessages();
        $this->createForumSections();
        $this->createForumTopic();
        $this->createForumMessageFiles();
    }

    private function createCmsForumFiles(): void
    {
        if ($this->schema->hasTable('cms_forum_files')) {
            return;
        }

        $this->schema->create('cms_forum_files', static function (TableDefinition $table): void {
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

    private function createCmsForumRdm(): void
    {
        if ($this->schema->hasTable('cms_forum_rdm')) {
            return;
        }

        $this->schema->create('cms_forum_rdm', static function (TableDefinition $table): void {
            $table->integer('topic_id')->unsigned()->default(0);
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0)->index('time');
            $table->primary(['topic_id', 'user_id'], 'topic_user');
        });
    }

    private function createCmsForumVote(): void
    {
        if ($this->schema->hasTable('cms_forum_vote')) {
            return;
        }

        $this->schema->create('cms_forum_vote', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('type')->default(0)->index('type');
            $table->integer('time')->unsigned()->default(0);
            $table->integer('topic')->unsigned()->default(0)->index('topic');
            $table->string('name');
            $table->integer('count')->unsigned()->default(0);
            $table->index(['type', 'topic'], 'type_topic');
        });
    }

    private function createCmsForumVoteUsers(): void
    {
        if ($this->schema->hasTable('cms_forum_vote_users')) {
            return;
        }

        $this->schema->create('cms_forum_vote_users', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user')->default(0);
            $table->integer('topic')->index('topic');
            $table->integer('vote');
            $table->index(['topic', 'user'], 'topic_user');
        });
    }

    private function createForumMessages(): void
    {
        if ($this->schema->hasTable('forum_messages')) {
            return;
        }

        $this->schema->create('forum_messages', static function (TableDefinition $table): void {
            $table->bigIncrements('id');
            $table->integer('topic_id')->index('topic_id');
            $table->longText('text');
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
            // What the forum search reads with MATCH ... AGAINST.
            $table->fullText('text', 'text');
        });
    }

    private function createForumSections(): void
    {
        if ($this->schema->hasTable('forum_sections')) {
            return;
        }

        $this->schema->create('forum_sections', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('parent')->nullable()->index('parent');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            $table->integer('sort')->default('100');
            $table->integer('access')->nullable();
            $table->integer('section_type')->nullable();
            $table->unique(['parent', 'slug'], 'forum_sections_parent_slug_unique');
        });
    }

    private function createForumTopic(): void
    {
        if ($this->schema->hasTable('forum_topic')) {
            return;
        }

        $this->schema->create('forum_topic', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('section_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
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
            $table->unique(['section_id', 'slug'], 'forum_topic_section_slug_unique');
        });
    }

    private function createForumMessageFiles(): void
    {
        if ($this->schema->hasTable('forum_message_files')) {
            return;
        }

        $this->schema->create('forum_message_files', static function (TableDefinition $table): void {
            $table->bigIncrements('id');
            $table->bigInteger('message_id')->unsigned()->index('forum_message_files_message_id');
            $table->bigInteger('file_id')->unsigned()->index('forum_message_files_file_id');
            $table->dateTime('created_at')->nullable();
            $table->unique(['message_id', 'file_id'], 'forum_message_files_message_file_unique');
        });
    }
};
