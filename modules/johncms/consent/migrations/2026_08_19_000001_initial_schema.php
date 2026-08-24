<?php

/**
 * The tables of the consent module as they stood when migrations were introduced.
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
        $this->createConsents();
        $this->createConsentLog();
    }

    private function createConsents(): void
    {
        if ($this->schema->hasTable('consents')) {
            return;
        }

        $this->schema->create('consents', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('context')->index();
            $table->string('language', 5)->index();
            // The title is shown next to the checkbox and may contain inline HTML with links.
            $table->text('title');
            $table->longText('text');
            $table->string('version');
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function createConsentLog(): void
    {
        if ($this->schema->hasTable('consent_log')) {
            return;
        }

        $this->schema->create('consent_log', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable()->index();
            $table->integer('consent_id')->unsigned()->index();
            $table->string('version');
            $table->string('ip_address', 45);
            $table->dateTime('accepted_at');
        });
    }
};
