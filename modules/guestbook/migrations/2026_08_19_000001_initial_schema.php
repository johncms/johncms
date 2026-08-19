<?php

/**
 * The tables of the guestbook module as they stood when migrations were introduced.
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
        $this->createGuest();
    }

    private function createGuest(): void
    {
        if ($this->schema->hasTable('guest')) {
            return;
        }

        $this->schema->create('guest', static function (TableDefinition $table): void {
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
        });
    }
};
