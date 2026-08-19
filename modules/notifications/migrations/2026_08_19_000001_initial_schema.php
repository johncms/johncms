<?php

/**
 * The tables of the notifications module as they stood when migrations were introduced.
 *
 * Part of the baseline, so it creates only what is missing: a site upgrading from 9.9 already has
 * them and has to arrive at the same schema as a fresh installation.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createNotifications();
    }

    private function createNotifications(): void
    {
        if ($this->schema->hasTable('notifications')) {
            return;
        }

        $this->schema->create('notifications', static function (TableDefinition $table): void {
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
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
            $table->index(['user_id', 'module', 'event_type', 'entity_id'], 'user_module_type_entity');
        });
    }
};
