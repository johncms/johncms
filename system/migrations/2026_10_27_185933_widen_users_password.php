<?php

/**
 * A site that started before 9.0 keeps the password column of the md5 days, VARCHAR(32), and the
 * baseline leaves an existing table alone. A bcrypt hash is 60 characters long, so the login that
 * rehashes an old password could not save it.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('users', static function (TableDefinition $table): void {
            $table->string('password')->default('')->change();
        });
    }
};
