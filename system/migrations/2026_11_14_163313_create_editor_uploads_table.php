<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * Who uploaded each picture of the text editor and which record it belongs to.
     *
     * A row without an owner is an upload whose form was not sent yet; a row without a file is a
     * place reserved while the file is being written.
     */
    public function up(): void
    {
        $this->schema->create('editor_uploads', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('file_id')->unsigned()->nullable()->unique();
            $table->integer('user_id')->unsigned();
            $table->string('directory', 64);
            $table->string('owner_type', 32)->nullable();
            $table->integer('owner_id')->unsigned()->nullable();
            $table->dateTime('created_at')->index();
            $table->index(['owner_type', 'owner_id']);
            $table->index(['user_id', 'owner_type']);
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('editor_uploads');
    }
};
