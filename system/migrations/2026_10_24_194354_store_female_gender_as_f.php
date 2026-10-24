<?php

/**
 * The female gender is stored as 'f' (Johncms\Users\Gender), not as 'zh'. The demo data of the
 * installer stored 'f' already and stays as it is.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->db->execute("UPDATE users SET sex = 'f' WHERE sex = 'zh'");
    }
};
