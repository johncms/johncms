<?php

/**
 * A field of a collection may be shown as a column of the list of its items in the panel.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('collection_fields', static function (TableDefinition $table): void {
            $table->boolean('in_list')->default(false);
        });
    }

    public function down(): void
    {
        $this->schema->alter('collection_fields', static function (TableDefinition $table): void {
            $table->dropColumn('in_list');
        });
    }
};
