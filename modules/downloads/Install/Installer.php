<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Downloads\Install;

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

        // Закладки в загрузках
        $schema->create(
            'download__bookmark',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->index('user_id');
                $table->integer('file_id')->index('file_id');
            }
        );

        // Категории в загрузках
        $schema->create(
            'download__category',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('sort')->default(0);
                $table->text('name');
                $table->integer('total')->unsigned()->default(0)->index('total');
                $table->text('rus_name');
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->text('desc');
            }
        );

        // Комментарии в загрузках
        $schema->create(
            'download__comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->index('sub_id');
                $table->integer('time');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        // Файлы в загрузках
        $schema->create(
            'download__files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->integer('type')->unsigned()->default(0)->index('type');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('rus_name');
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->string('rate')->default('0|0');
                $table->text('about');
                $table->text('desc');
                $table->integer('comm_count')->unsigned()->default(0)->index('comm_count');
            }
        );

        // Доп файлы в загрузках
        $schema->create(
            'download__more',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->text('rus_name');
                $table->integer('size')->unsigned()->default(0);
            }
        );
    }
}
