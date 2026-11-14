<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /** What the owner of a guestbook has read is kept by the profile module (profile__guestbook_reads). */
    public function up(): void
    {
        $this->schema->alter('users', static function (TableDefinition $table): void {
            $table->dropColumn('comm_old');
        });
    }
};
