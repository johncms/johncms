<?php

/**
 * The tables of the mail module as they stood when migrations were introduced.
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
        $this->createCmsContact();
        $this->createCmsMail();
    }

    private function createCmsContact(): void
    {
        if ($this->schema->hasTable('cms_contact')) {
            return;
        }

        $this->schema->create('cms_contact', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->default(0);
            $table->integer('from_id')->unsigned()->default(0);
            $table->integer('time')->unsigned()->default(0)->index('time');
            $table->tinyInteger('type')->unsigned()->default(1);
            $table->tinyInteger('friends')->unsigned()->default(0);
            $table->tinyInteger('ban')->unsigned()->default(0)->index('ban');
            $table->tinyInteger('man')->unsigned()->default(0);
            $table->unique(['user_id', 'from_id'], 'id_user');
        });
    }

    private function createCmsMail(): void
    {
        if ($this->schema->hasTable('cms_mail')) {
            return;
        }

        $this->schema->create('cms_mail', static function (TableDefinition $table): void {
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
        });
    }
};
