<?php

/**
 * The entry an entry answers. An answer outlives the entry it answers: the column is left pointing
 * at nothing when that one is deleted, and the answer is shown without the link.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('guest', static function (TableDefinition $table): void {
            $table->integer('reply_to_id')->unsigned()->nullable()->index('guest_reply_to_id');
        });
    }

    public function down(): void
    {
        $this->schema->alter('guest', static function (TableDefinition $table): void {
            $table->dropIndex('guest_reply_to_id');
            $table->dropColumn('reply_to_id');
        });
    }
};
