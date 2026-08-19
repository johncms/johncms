<?php

/**
 * The tables of the album module as they stood when migrations were introduced.
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
        $this->createCmsAlbumCat();
        $this->createCmsAlbumComments();
        $this->createCmsAlbumDownloads();
        $this->createCmsAlbumFiles();
        $this->createCmsAlbumViews();
        $this->createCmsAlbumVotes();
    }

    private function createCmsAlbumCat(): void
    {
        if ($this->schema->hasTable('cms_album_cat')) {
            return;
        }

        $this->schema->create('cms_album_cat', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->integer('sort')->unsigned()->default(0);
            $table->string('name');
            $table->text('description');
            $table->string('password')->nullable();
            $table->integer('access')->nullable()->index('access');
        });
    }

    private function createCmsAlbumComments(): void
    {
        if ($this->schema->hasTable('cms_album_comments')) {
            return;
        }

        $this->schema->create('cms_album_comments', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
            $table->integer('time')->unsigned()->default(0);
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->text('text');
            $table->text('reply');
            $table->text('attributes');
        });
    }

    private function createCmsAlbumDownloads(): void
    {
        if ($this->schema->hasTable('cms_album_downloads')) {
            return;
        }

        $this->schema->create('cms_album_downloads', static function (TableDefinition $table): void {
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('file_id')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0);
            $table->primary(['user_id', 'file_id'], 'user_file');
        });
    }

    private function createCmsAlbumFiles(): void
    {
        if ($this->schema->hasTable('cms_album_files')) {
            return;
        }

        $this->schema->create('cms_album_files', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->integer('album_id')->unsigned()->index('album_id');
            $table->text('description');
            $table->string('img_name')->default('');
            $table->string('tmb_name')->default('');
            $table->integer('time')->unsigned()->default(0);
            $table->boolean('comments')->default(1);
            $table->integer('comm_count')->unsigned()->default(0);
            $table->tinyInteger('access')->unsigned()->default(0)->index('access');
            $table->integer('vote_plus')->default(0);
            $table->integer('vote_minus')->default(0);
            $table->integer('views')->unsigned()->default(0);
            $table->integer('downloads')->unsigned()->default(0);
            $table->boolean('unread_comments')->default(0);
        });
    }

    private function createCmsAlbumViews(): void
    {
        if ($this->schema->hasTable('cms_album_views')) {
            return;
        }

        $this->schema->create('cms_album_views', static function (TableDefinition $table): void {
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('file_id')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0);
            $table->primary(['user_id', 'file_id'], 'user_file');
        });
    }

    private function createCmsAlbumVotes(): void
    {
        if ($this->schema->hasTable('cms_album_votes')) {
            return;
        }

        $this->schema->create('cms_album_votes', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->integer('file_id')->unsigned()->default(0)->index('file_id');
            $table->tinyInteger('vote');
        });
    }
};
