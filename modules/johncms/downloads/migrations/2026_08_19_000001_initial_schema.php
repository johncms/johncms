<?php

/**
 * The tables of the downloads module as they stood when migrations were introduced.
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
        $this->createDownloadBookmark();
        $this->createDownloadCategory();
        $this->createDownloadComments();
        $this->createDownloadFiles();
        $this->createDownloadMore();
    }

    private function createDownloadBookmark(): void
    {
        if ($this->schema->hasTable('download__bookmark')) {
            return;
        }

        $this->schema->create('download__bookmark', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->index('user_id');
            $table->integer('file_id')->index('file_id');
        });
    }

    private function createDownloadCategory(): void
    {
        if ($this->schema->hasTable('download__category')) {
            return;
        }

        $this->schema->create('download__category', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('refid')->unsigned()->default(0)->index('refid');
            $table->text('dir');
            $table->integer('sort')->default(0);
            $table->text('name');
            $table->string('slug')->nullable();
            $table->integer('total')->unsigned()->default(0)->index('total');
            $table->text('rus_name');
            $table->text('text');
            $table->integer('field')->unsigned()->default(0);
            $table->text('desc');
            $table->unique(['refid', 'slug'], 'download__category_refid_slug_unique');
        });
    }

    private function createDownloadComments(): void
    {
        if ($this->schema->hasTable('download__comments')) {
            return;
        }

        $this->schema->create('download__comments', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('sub_id')->unsigned()->index('sub_id');
            $table->integer('time');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->text('text');
            $table->text('reply');
            $table->text('attributes');
        });
    }

    private function createDownloadFiles(): void
    {
        if ($this->schema->hasTable('download__files')) {
            return;
        }

        $this->schema->create('download__files', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('refid')->unsigned()->default(0)->index('refid');
            $table->text('dir');
            $table->integer('time')->unsigned()->default(0)->index('time');
            $table->text('name');
            $table->integer('type')->unsigned()->default(0)->index('type');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->text('rus_name');
            $table->string('slug')->nullable();
            $table->text('text');
            $table->integer('field')->unsigned()->default(0);
            $table->string('rate')->default('0|0');
            $table->text('about');
            $table->text('desc');
            $table->integer('comm_count')->unsigned()->default(0)->index('comm_count');
            $table->unique(['refid', 'slug'], 'download__files_refid_slug_unique');
        });
    }

    private function createDownloadMore(): void
    {
        if ($this->schema->hasTable('download__more')) {
            return;
        }

        $this->schema->create('download__more', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('refid')->unsigned()->default(0)->index('refid');
            $table->integer('time')->unsigned()->default(0)->index('time');
            $table->text('name');
            $table->text('rus_name');
            $table->integer('size')->unsigned()->default(0);
        });
    }
};
