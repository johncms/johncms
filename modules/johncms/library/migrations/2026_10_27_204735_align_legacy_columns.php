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
        $this->schema->alter('library_cats', static function (TableDefinition $table): void {
            $table->string('name')->default('')->change();
        });

        $this->schema->alter('library_texts', static function (TableDefinition $table): void {
            $table->string('name')->default('')->change();
            $table->string('uploader')->default('')->change();
        });

        $this->schema->alter('library_tags', static function (TableDefinition $table): void {
            $table->string('tag_name')->default('')->change();
        });

        $this->schema->alter('cms_library_comments', static function (TableDefinition $table): void {
            $table->integer('sub_id')->unsigned()->default(0)->change();
            $table->integer('time')->default(0)->change();
            $table->integer('user_id')->unsigned()->default(0)->change();
        });
    }
};
