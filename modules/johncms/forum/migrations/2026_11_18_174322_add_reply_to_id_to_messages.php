<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * The post a post answers, in the same topic. Only the direct one is kept: a topic is a flat
     * feed, and a chain of answers is followed one link at a time.
     *
     * The answer outlives the post it answered: deleting that one for good leaves the answer
     * without the link. Hiding it keeps the link. The posts written before have none, and none is
     * guessed from their quotes.
     */
    public function up(): void
    {
        $this->schema->alter('forum_messages', static function (TableDefinition $table): void {
            $table->bigInteger('reply_to_id')->unsigned()->nullable();
            $table->index('reply_to_id', 'forum_messages_reply_to');
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('forum_messages')
                ->onDelete(ReferentialAction::SetNull);
        });
    }

    public function down(): void
    {
        $this->schema->alter('forum_messages', static function (TableDefinition $table): void {
            $table->dropForeignForColumns('reply_to_id');
            $table->dropIndex('forum_messages_reply_to');
            $table->dropColumn('reply_to_id');
        });
    }
};
