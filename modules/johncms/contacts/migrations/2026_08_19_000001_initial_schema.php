<?php

/**
 * The tables of the contacts module as they stood when migrations were introduced.
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
        $this->createContactMessages();
    }

    private function createContactMessages(): void
    {
        if ($this->schema->hasTable('contact_messages')) {
            return;
        }

        $this->schema->create('contact_messages', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->string('name');
            $table->string('email');
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->string('ip_address', 45);
            $table->string('user_agent')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->timestamps();
        });
    }
};
