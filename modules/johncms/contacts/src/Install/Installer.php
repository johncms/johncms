<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Install;

use Illuminate\Database\Capsule\Manager as Capsule;

class Installer extends \Johncms\Modules\Installer
{
    public function uninstall(): void
    {
        Capsule::schema()->dropIfExists('contact_messages');
    }
}
