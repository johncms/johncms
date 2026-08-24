<?php

/**
 * The tables of the library module as they stood when migrations were introduced.
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
        $this->createLibraryCats();
        $this->createLibraryTexts();
        $this->createLibraryTags();
        $this->createCmsLibraryComments();
        $this->createCmsLibraryRating();
    }

    private function createLibraryCats(): void
    {
        if ($this->schema->hasTable('library_cats')) {
            return;
        }

        $this->schema->create('library_cats', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('parent')->unsigned()->default(0);
            $table->string('name')->default('');
            $table->string('slug')->nullable();
            $table->text('description');
            $table->boolean('dir')->default(0);
            $table->integer('pos')->unsigned()->default(0);
            $table->boolean('user_add')->default(0);
            $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
        });
    }

    private function createLibraryTexts(): void
    {
        if ($this->schema->hasTable('library_texts')) {
            return;
        }

        $this->schema->create('library_texts', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('cat_id')->unsigned()->default(0);
            $table->mediumText('text');
            $table->string('name')->default('')->index('name');
            $table->string('slug')->nullable();
            $table->text('announce');
            $table->string('uploader')->default('');
            $table->integer('uploader_id')->unsigned()->default(0);
            $table->integer('count_views')->unsigned()->default(0);
            $table->boolean('premod')->default(0);
            $table->boolean('comments')->default(0);
            $table->integer('comm_count')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0);
            // What the article search reads with MATCH ... AGAINST.
            $table->fullText('text', 'text');
            $table->fullText('name', 'idx_name');
        });
    }

    private function createLibraryTags(): void
    {
        if ($this->schema->hasTable('library_tags')) {
            return;
        }

        $this->schema->create('library_tags', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('lib_text_id')->unsigned()->default(0)->index('lib_text_id');
            $table->string('tag_name')->default('')->index('tag_name');
        });
    }

    private function createCmsLibraryComments(): void
    {
        if ($this->schema->hasTable('cms_library_comments')) {
            return;
        }

        $this->schema->create('cms_library_comments', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
            $table->integer('time')->default(0);
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->text('text');
            $table->text('reply');
            $table->text('attributes');
        });
    }

    private function createCmsLibraryRating(): void
    {
        if ($this->schema->hasTable('cms_library_rating')) {
            return;
        }

        $this->schema->create('cms_library_rating', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->integer('st_id')->unsigned();
            $table->tinyInteger('point');
            $table->index(['user_id', 'st_id'], 'user_article');
        });
    }
};
