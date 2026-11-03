<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->create('news_legacy_cleanup_files', static function (TableDefinition $table): void {
            $table->bigInteger('file_id')->unsigned();
            $table->primary('file_id');
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('news_legacy_cleanup_files');
    }
};
