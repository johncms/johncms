<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('collection_file_links', static function (TableDefinition $table): void {
            $table->integer('field_id')->unsigned()->nullable()->index('collection_upload_field');
        });
    }

    public function down(): void
    {
        $this->schema->alter('collection_file_links', static function (TableDefinition $table): void {
            $table->dropIndex('collection_upload_field');
            $table->dropColumn('field_id');
        });
    }
};
