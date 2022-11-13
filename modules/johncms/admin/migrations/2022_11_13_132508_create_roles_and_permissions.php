<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Johncms\Users\Permission;
use Johncms\Users\Role;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
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

        $permissions = [
            [
                'name'         => \Johncms\Admin\AdminPermissions::USER_MANAGEMENT,
                'display_name' => __('Access to user management'),
                'module_name'  => 'johncms/admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->create($permission);
        }

        // Attach permissions to roles
        $permissions = Permission::query()->where('module_name', 'johncms/admin')->get()->pluck('id');
        $adminRole = Role::query()->where('name', 'admin')->first();
        $adminRole->permissions()->syncWithoutDetaching($permissions);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
};
