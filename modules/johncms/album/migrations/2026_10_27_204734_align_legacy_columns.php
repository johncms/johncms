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
        $this->schema->alter('cms_album_cat', static function (TableDefinition $table): void {
            $table->string('name')->change();
            $table->string('password')->nullable()->change();
            $table->integer('access')->nullable()->change();
        });

        $this->schema->alter('cms_album_files', static function (TableDefinition $table): void {
            $table->string('img_name')->default('')->change();
            $table->string('tmb_name')->default('')->change();
        });
    }
};
