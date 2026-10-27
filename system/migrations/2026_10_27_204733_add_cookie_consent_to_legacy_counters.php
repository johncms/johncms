<?php

/**
 * In 9.x the column came with a separate update script that not every site ran, and the baseline
 * leaves an existing table alone. The check is what lets a site that did run it pass.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        if ($this->schema->hasColumn('cms_counters', 'require_cookie_consent')) {
            return;
        }

        $this->schema->alter('cms_counters', static function (TableDefinition $table): void {
            $table->boolean('require_cookie_consent')->default(0);
        });
    }
};
