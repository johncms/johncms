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
        $this->schema->alter('download__files', static function (TableDefinition $table): void {
            $table->string('rate')->default('0|0')->change();
        });

        $this->schema->alter('download__more', static function (TableDefinition $table): void {
            $table->integer('refid')->unsigned()->default(0)->change();
            $table->integer('time')->unsigned()->default(0)->change();
            $table->integer('size')->unsigned()->default(0)->change();
        });
    }
};
