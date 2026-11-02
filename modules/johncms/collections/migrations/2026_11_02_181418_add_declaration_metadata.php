<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('collections', static function (TableDefinition $table): void {
            $table->string('declaration_key')->nullable();
            $table->string('label_domain')->nullable();
            $table->unique(['owner_module', 'declaration_key'], 'collections_declaration');
        });
        $this->schema->alter('collection_fields', static function (TableDefinition $table): void {
            $table->string('owner_module')->nullable();
            $table->string('label_domain')->nullable();
            $table->string('role')->nullable();
            $table->unique(['collection_id', 'role'], 'collection_fields_role');
        });
    }

    public function down(): void
    {
        $this->schema->alter('collection_fields', static function (TableDefinition $table): void {
            $table->dropUnique('collection_fields_role');
            $table->dropColumn('owner_module', 'label_domain', 'role');
        });
        $this->schema->alter('collections', static function (TableDefinition $table): void {
            $table->dropUnique('collections_declaration');
            $table->dropColumn('declaration_key', 'label_domain');
        });
    }
};
