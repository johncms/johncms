<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Album\Install;

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
        $schema->create(
            'cms_album_cat',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->integer('sort')->unsigned()->default(0);
                $table->string('name');
                $table->text('description');
                $table->string('password')->nullable();
                $table->integer('access')->nullable()->index('access');
            }
        );

        $schema->create(
            'cms_album_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
                $table->integer('time')->unsigned()->default(0);
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        $schema->create(
            'cms_album_downloads',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_files',
            static function (Blueprint $table) {
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
            }
        );

        $schema->create(
            'cms_album_views',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_votes',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->integer('file_id')->unsigned()->default(0)->index('file_id');
                $table->tinyInteger('vote');
            }
        );
    }
}
