<?php

/**
 * The replies of the staff are entries of their own now: the columns that held them go.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->schema->alter('guest', static function (TableDefinition $table): void {
            $table->dropColumn('admin', 'otvet', 'otime');
        });
    }
};
