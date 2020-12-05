<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Library\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createTables();
    }

    public function uninstall(): void
    {
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        $connection = Capsule::connection();

        // Библиотека
        $schema->create(
            'library_cats',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('description');
                $table->boolean('dir')->default(0);
                $table->integer('pos')->unsigned()->default(0);
                $table->boolean('user_add')->default(0);
            }
        );

        $schema->create(
            'library_texts',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('cat_id')->unsigned()->default(0);
                $table->mediumText('text');
                $table->string('name')->default('')->index('name');
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
