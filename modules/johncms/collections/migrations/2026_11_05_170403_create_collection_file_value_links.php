<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('collection_file_value_links', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('field_id')->unsigned()->index();
            $table->integer('item_id')->unsigned()->nullable()->index();
            $table->bigInteger('file_id')->unsigned()->index();
            $table->unique(['field_id', 'item_id', 'file_id'], 'collection_file_value_reference');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('collection_file_value_links');
    }
};
