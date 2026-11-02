<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('collection_sections', static function (TableDefinition $table): void {
            $table->text('metadata')->nullable();
        });
    }

    public function down(): void
    {
        $this->schema->alter('collection_sections', static function (TableDefinition $table): void {
            $table->dropColumn('metadata');
        });
    }
};
