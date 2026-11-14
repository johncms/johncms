<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /** The pictures of the entries are held by the registry of the editor uploads. */
    public function up(): void
    {
        $this->schema->alter('guest', static function (TableDefinition $table): void {
            $table->dropColumn('attached_files');
        });
    }
};
