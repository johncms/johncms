<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * How far the owner of a guestbook has read it: the last entry seen, by id. Entries get their
     * ids in the order they are written, so whatever comes after is unread — even when several
     * arrive within one second, and whatever was deleted since.
     */
    public function up(): void
    {
        $this->schema->create('profile__guestbook_reads', static function (TableDefinition $table): void {
            $table->integer('user_id')->unsigned()->primary();
            $table->integer('seen_comment_id')->unsigned()->default(0);
        });
    }

    public function down(): void
    {
        $this->schema->dropIfExists('profile__guestbook_reads');
    }
};
