<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Notifications\Install;

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
            'notifications',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->string('module')->comment('Module name');
                $table->string('event_type')->comment('Event type');
                $table->integer('user_id')->unsigned()->index()->comment('User identifier');
                $table->integer('sender_id')->unsigned()->nullable()->comment('Sender identifier');
                $table->integer('entity_id')->unsigned()->nullable()->comment('Entity identifier');
                $table->text('fields')->nullable()->comment('Event fields');
                $table->timestamp('read_at')->nullable()->comment('Read date');
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
                $table->index(['user_id', 'module', 'event_type', 'entity_id'], 'user_module_type_entity');
            }
        );
    }
}
