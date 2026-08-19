<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Install;

use Illuminate\Database\Capsule\Manager as Capsule;

class Installer extends \Johncms\Modules\Installer
{
    public function uninstall(): void
    {
        $schema = Capsule::schema();
        $schema->dropIfExists('consent_log');
        $schema->dropIfExists('consents');
    }
}
