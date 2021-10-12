<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Admin\Install;

use Johncms\Users\Role;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createRoles();
    }

    public function uninstall(): void
    {
    }

    private function createRoles()
    {
        $role = new Role();
        $role->create(
            [
                'name'         => 'admin',
                'display_name' => 'Administrator',
            ]
        );
        $role->create(
            [
                'name'         => 'moderator',
                'display_name' => 'Moderator',
            ]
        );
    }
}
