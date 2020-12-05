<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Mail\Install;

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

        // Контакты
        $schema->create(
            'cms_contact',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('from_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->tinyInteger('type')->unsigned()->default(1);
                $table->tinyInteger('friends')->unsigned()->default(0);
                $table->tinyInteger('ban')->unsigned()->default(0)->index('ban');
                $table->tinyInteger('man')->unsigned()->default(0);
                $table->unique(['user_id', 'from_id'], 'id_user');
            }
        );

        // Почта
        $schema->create(
            'cms_mail',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->integer('from_id')->unsigned()->default(0)->index('from_id');
                $table->text('text');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->boolean('read')->default(0)->index('read');
                $table->boolean('sys')->default(0)->index('sys');
                $table->integer('delete')->unsigned()->default(0)->index('delete');
                $table->string('file_name')->default('');
                $table->integer('count')->default(0);
                $table->integer('size')->default(0);
                $table->string('them')->default('');
                $table->boolean('spam')->default(0);
            }
        );
    }
}
