<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Guestbook\Install;

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
        // Гостевая
        $schema->create(
            'guest',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('adm')->default(0)->index('adm');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->integer('user_id')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('text');
                $table->bigInteger('ip')->default(0)->index('ip');
                $table->string('browser')->default('');
                $table->string('admin')->default('');
                $table->text('otvet');
                $table->integer('otime')->unsigned()->default(0);
                $table->string('edit_who')->default('');
                $table->integer('edit_time')->unsigned()->default(0);
                $table->tinyInteger('edit_count')->unsigned()->default(0);
                $table->longText('attached_files')->nullable();
            }
        );
    }
}
