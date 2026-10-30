<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * The ban by IP address is gone: it knew IPv4 only, so a visitor on IPv6 passed any ban, and an
     * address is shared or changed too easily to stand for a person. Blocking addresses belongs to
     * the web server or the firewall. No way back: the bans it held would mean nothing without the
     * check that read them.
     */
    public function up(): void
    {
        $this->schema->dropIfExists('cms_ban_ip');
    }
};
