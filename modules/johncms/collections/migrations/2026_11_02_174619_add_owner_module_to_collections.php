<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('collections', static function (TableDefinition $table): void {
            $table->string('owner_module')->nullable()->index();
        });
    }

    public function down(): void
    {
        $this->schema->alter('collections', static function (TableDefinition $table): void {
            $table->dropIndex('collections_owner_module_index');
            $table->dropColumn('owner_module');
        });
    }
};
