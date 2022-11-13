<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Guestbook\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Users\Permission;
use Johncms\Users\Role;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createTables();
        $this->createPermissions();
    }

    public function uninstall(): void
    {
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        // Гостевая
        $schema->create(
            'guest',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('adm')->default(0)->index('adm');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->integer('user_id')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('text');
                $table->bigInteger('ip')->default(0)->index('ip');
                $table->string('browser')->default('');
                $table->string('admin')->default('');
                $table->text('otvet');
                $table->integer('otime')->unsigned()->default(0);
                $table->string('edit_who')->default('');
                $table->integer('edit_time')->unsigned()->default(0);
                $table->tinyInteger('edit_count')->unsigned()->default(0);
                $table->longText('attached_files')->nullable();
            }
        );
    }

    private function createPermissions()
    {
        $permissions = [
            [
                'name'         => 'guestbook_admin_club',
                'display_name' => __('Access to the admin club'),
                'module_name'  => $this->moduleName,
            ],
            [
                'name'         => 'guestbook_delete_posts',
                'display_name' => __('Access to delete the guestbook posts'),
                'module_name'  => $this->moduleName,
            ],
            [
                'name'         => 'guestbook_clear',
                'display_name' => __('Access to clear the guestbook'),
                'module_name'  => $this->moduleName,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->create($permission);
        }
    }

    public function afterInstall(): void
    {
        $permissions = Permission::query()->where('module_name', $this->moduleName)->get()->pluck('id');

        $adminRole = Role::query()->where('name', 'admin')->first();
        $moderatorRole = Role::query()->where('name', 'moderator')->first();

        // Attach permissions to roles
        $adminRole->permissions()->syncWithoutDetaching($permissions);
        $moderatorRole->permissions()->syncWithoutDetaching($permissions);
    }
}
