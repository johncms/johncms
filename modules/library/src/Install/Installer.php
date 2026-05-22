<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Library\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createTables();
        $this->addSlugColumns();
    }

    private function addSlugColumns(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('library_cats') && ! $schema->hasColumn('library_cats', 'slug')) {
            $schema->table('library_cats', static function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
                $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
            });
            $this->generateCategorySlugs();
        }

        if ($schema->hasTable('library_texts') && ! $schema->hasColumn('library_texts', 'slug')) {
            $schema->table('library_texts', static function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });
            $this->generateArticleSlugs();
        }
    }

    private function generateCategorySlugs(): void
    {
        $categories = Capsule::table('library_cats')->orderBy('id')->get();
        $usedSlugs = [];

        foreach ($categories as $category) {
            $baseSlug = \Illuminate\Support\Str::slug($category->name);
            if ($baseSlug === '') {
                $baseSlug = 'section';
            }

            $key = $category->parent . ':' . $baseSlug;
            $slug = $baseSlug;
            $suffix = 2;
            while (in_array($category->parent . ':' . $slug, $usedSlugs, true)) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $usedSlugs[] = $category->parent . ':' . $slug;
            Capsule::table('library_cats')->where('id', $category->id)->update(['slug' => $slug]);
        }
    }

    private function generateArticleSlugs(): void
    {
        $articles = Capsule::table('library_texts')->orderBy('id')->get();
        $usedSlugs = [];

        foreach ($articles as $article) {
            $baseSlug = \Illuminate\Support\Str::slug($article->name);
            if ($baseSlug === '') {
                $baseSlug = 'article';
            }

            $slug = $baseSlug;
            $suffix = 2;
            while (in_array($article->cat_id . ':' . $slug, $usedSlugs, true)) {
                $slug = $baseSlug . '-' . $suffix;
                ++$suffix;
            }

            $usedSlugs[] = $article->cat_id . ':' . $slug;
            Capsule::table('library_texts')->where('id', $article->id)->update(['slug' => $slug]);
        }
    }

    public function uninstall(): void
    {
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        $connection = Capsule::connection();

        // Библиотека
        if (! $schema->hasTable('library_cats')) {
            $schema->create(
                'library_cats',
                static function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('parent')->unsigned()->default(0);
                    $table->string('name')->default('');
                    $table->string('slug')->nullable();
                    $table->text('description');
                    $table->boolean('dir')->default(0);
                    $table->integer('pos')->unsigned()->default(0);
                    $table->boolean('user_add')->default(0);
                    $table->unique(['parent', 'slug'], 'library_cats_parent_slug_unique');
                }
            );
        }

        if (! $schema->hasTable('library_texts')) {
            $schema->create(
                'library_texts',
                static function (Blueprint $table) {
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
                }
            );
            $connection->statement('ALTER TABLE `library_texts` ADD FULLTEXT `text` (`text`)');
            $connection->statement('ALTER TABLE `library_texts` ADD FULLTEXT `idx_name` (`name`)');
        }

        $schema->create(
            'library_tags',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('lib_text_id')->unsigned()->default(0)->index('lib_text_id');
                $table->string('tag_name')->default('')->index('tag_name');
            }
        );

        $schema->create(
            'cms_library_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
                $table->integer('time')->default(0);
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        $schema->create(
            'cms_library_rating',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('st_id')->unsigned();
                $table->tinyInteger('point');
                $table->index(['user_id', 'st_id'], 'user_article');
            }
        );
    }
}
