<?php

/**
 * The demo data of the installer gave its female user the gender 'f', which the site does not
 * know: the forms accept 'm' and 'zh' only.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        $this->db->execute("UPDATE users SET sex = 'zh' WHERE sex = 'f'");
    }
};
