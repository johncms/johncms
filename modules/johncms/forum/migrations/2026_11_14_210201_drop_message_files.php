<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    /** The pictures of the posts are held by the registry of the editor uploads. */
    public function up(): void
    {
        $this->schema->dropIfExists('forum_message_files');
    }
};
