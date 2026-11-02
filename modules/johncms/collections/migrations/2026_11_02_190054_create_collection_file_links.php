<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('collection_upload_contexts', static function (TableDefinition $table): void {
            $table->string('token', 64);
            $table->primary('token');
            $table->integer('collection_id')->unsigned()->index();
            $table->integer('user_id')->unsigned();
            $table->integer('item_id')->unsigned()->nullable()->index();
            $table->dateTime('created_at')->index();
        });
        $this->schema->create('collection_file_assets', static function (TableDefinition $table): void {
            $table->bigInteger('file_id')->unsigned();
            $table->primary('file_id');
            $table->boolean('managed')->default(false);
        });
        $this->schema->create('collection_file_links', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->bigInteger('file_id')->unsigned()->index();
            $table->integer('item_id')->unsigned()->nullable();
            $table->string('context_token', 64)->nullable();
            $table->unique(['item_id', 'file_id'], 'collection_item_file');
            $table->unique(['context_token', 'file_id'], 'collection_context_file');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('collection_file_links');
        $this->schema->dropIfExists('collection_file_assets');
        $this->schema->dropIfExists('collection_upload_contexts');
    }
};
