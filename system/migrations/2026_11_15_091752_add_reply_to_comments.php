<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * The comment a comment answers, under the same subject. Only the direct one is kept: the
     * discussion is a flat feed, and a chain of answers is still followed one link at a time.
     *
     * The answer outlives the comment it answered: deleting that one leaves the answer without
     * the link. The comments written before have none, and none is guessed from their text.
     */
    public function up(): void
    {
        $this->schema->alter('comments', static function (TableDefinition $table): void {
            $table->integer('reply_to_id')->unsigned()->nullable();
            $table->index('reply_to_id', 'comments_reply_to');
            $table->foreign('reply_to_id')
                ->references('id')
                ->on('comments')
                ->onDelete(ReferentialAction::SetNull);
        });
    }

    public function down(): void
    {
        $this->schema->alter('comments', static function (TableDefinition $table): void {
            $table->dropForeignForColumns('reply_to_id');
            $table->dropIndex('comments_reply_to');
            $table->dropColumn('reply_to_id');
        });
    }
};
