<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * The guestbooks of the profiles live in the comments of the core now; the previous migration
     * carried them over. No way back: the replies became entries of their own.
     */
    public function up(): void
    {
        $this->schema->dropIfExists('cms_users_guestbook');
    }
};
