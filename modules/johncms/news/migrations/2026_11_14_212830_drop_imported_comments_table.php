<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * The comments of the news are those of the core. A site whose news of 9.x are imported
     * already (the table lost its article_id then) needs the table no more; on a site still
     * waiting for system:update it holds the comments the import carries, and the import drops it.
     */
    public function up(): void
    {
        if (! $this->schema->hasColumn('news_comments', 'article_id')) {
            $this->schema->drop('news_comments');
        }
    }
};
