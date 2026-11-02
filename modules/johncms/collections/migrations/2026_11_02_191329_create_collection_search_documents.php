<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('collection_search_documents', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('collection_id')->unsigned()->index();
            $table->integer('item_id')->unsigned();
            $table->string('reference', 261);
            $table->longText('plain_text');
            $table->unique(['item_id', 'reference'], 'collection_search_item_reference');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('collection_search_documents');
    }
};
