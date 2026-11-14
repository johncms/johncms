<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * The comments of the articles live in the comments of the core now; the previous migration
     * carried them over. No way back: the replies became comments of their own.
     */
    public function up(): void
    {
        $this->schema->dropIfExists('cms_library_comments');
    }
};
