<?php

/**
 * A site upgraded from an older release keeps the columns in the shape that release gave them,
 * and the baseline leaves an existing table alone: NOT NULL without a default, or shorter than
 * what the code writes. The columns are brought to what the baseline creates on a fresh site.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('users', static function (TableDefinition $table): void {
            $table->string('name', 100)->default('')->change();
            $table->string('name_lat', 100)->default('')->change();
            $table->string('imname')->default('')->change();
            $table->string('sex', 5)->default('')->change();
            $table->string('mail')->default('')->change();
            $table->string('skype')->default('')->change();
            $table->string('jabber')->default('')->change();
            $table->string('www')->default('')->change();
            $table->text('about')->nullable()->change();
            $table->string('live')->default('')->change();
            $table->string('mibile')->default('')->change();
            $table->string('status')->default('')->change();
            $table->string('regadm')->default('')->change();
            $table->text('place')->nullable()->change();
            $table->text('set_user')->nullable()->change();
            $table->text('set_forum')->nullable()->change();
            $table->text('set_mail')->nullable()->change();
            $table->text('smileys')->nullable()->change();
            $table->string('confirmation_code')->nullable()->change();
            $table->string('new_email')->nullable()->change();
        });

        $this->schema->alter('cms_ads', static function (TableDefinition $table): void {
            $table->tinyInteger('type')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('view')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('layout')->unsigned()->default(0)->nullable()->change();
            $table->integer('count')->unsigned()->default(0)->nullable()->change();
            $table->integer('count_link')->unsigned()->default(0)->nullable()->change();
            $table->integer('to')->unsigned()->default(0)->nullable()->change();
            $table->string('color')->default('')->nullable()->change();
            $table->integer('time')->unsigned()->default(0)->nullable()->change();
            $table->integer('day')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('mesto')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('bold')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('italic')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('underline')->unsigned()->default(0)->nullable()->change();
            $table->tinyInteger('show')->unsigned()->default(0)->nullable()->change();
        });

        $this->schema->alter('cms_ban_ip', static function (TableDefinition $table): void {
            $table->string('link')->default('')->change();
            $table->string('who')->default('')->change();
            $table->integer('date')->default(0)->change();
        });

        $this->schema->alter('cms_ban_users', static function (TableDefinition $table): void {
            $table->string('ban_who')->default('')->change();
            $table->string('ban_raz')->default('')->change();
        });

        $this->schema->alter('cms_counters', static function (TableDefinition $table): void {
            $table->string('name')->default('')->change();
        });

        $this->schema->alter('cms_sessions', static function (TableDefinition $table): void {
            $table->string('browser')->default('')->change();
        });

        $this->schema->alter('cms_users_data', static function (TableDefinition $table): void {
            $table->string('key')->default('')->change();
        });

        $this->schema->alter('karma_users', static function (TableDefinition $table): void {
            $table->string('name')->default('')->change();
        });

        $this->schema->alter('files', static function (TableDefinition $table): void {
            $table->string('storage')->change();
            $table->string('name')->change();
            $table->string('path')->change();
        });
    }
};
