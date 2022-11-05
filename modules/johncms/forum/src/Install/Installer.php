<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Forum\Install;

use Johncms\Forum\ForumPermissions;
use Johncms\Users\Permission;
use Johncms\Users\Role;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createPermissions();
    }

    public function uninstall(): void
    {
    }

    private function createPermissions()
    {
        $permissions = [
            [
                'name'         => ForumPermissions::MANAGE_POSTS,
                'display_name' => __('Access to forum message management'),
                'module_name'  => $this->module_name,
            ],
            [
                'name'         => ForumPermissions::MANAGE_TOPICS,
                'display_name' => __('Access to forum topic management'),
                'module_name'  => $this->module_name,
            ],
            [
                'name'         => ForumPermissions::COMPLETE_DELETE_TOPIC,
                'display_name' => __('Access to the complete removal of the topic'),
                'module_name'  => $this->module_name,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->create($permission);
        }
    }

    public function afterInstall(): void
    {
        $permissions = Permission::query()->where('module_name', $this->module_name)->get()->pluck('id');

        $adminRole = Role::query()->where('name', 'admin')->first();
        $moderatorRole = Role::query()->where('name', 'moderator')->first();

        // Attach permissions to roles
        $adminRole->permissions()->sync($permissions);
        $moderatorRole->permissions()->sync($permissions);
    }
}
