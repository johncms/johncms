<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Tests\Unit\Users;

use Johncms\Cache;
use Johncms\Users\Permission;
use Johncms\Users\Role;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class UserPermissionCheckerTest extends AbstractTestCase
{
    public UserManager $userManager;

    public array $userFields = [];

    protected function setUp(): void
    {
        $this->dropTables();
        $this->runMigrations();
        $this->userManager = di(UserManager::class);
        $this->userFields = [
            'login'    => $this->faker->userName(),
            'email'    => $this->faker->email(),
            'phone'    => $this->faker->phoneNumber(),
            'password' => $this->faker->password(),
        ];

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

        parent::setUp();
    }

    public function testHasPermission()
    {
        // Test create
        $created_user = $this->userManager->create($this->userFields);
        $cache = di(Cache::class);
        $cache->clear();
        $this->assertFalse($created_user->hasPermission('test_permission_1'));

        // Create permissions
        $permission_1 = (new Permission())->create(['name' => 'test_permission_1', 'display_name' => 'Test permission']);
        $permission_2 = (new Permission())->create(['name' => 'test_permission_2', 'display_name' => 'Test permission 2']);

        $adminRole = (new Role())->where('name', 'admin')->first();
        $moderatorRole = (new Role())->where('name', 'moderator')->first();

        // Attach permissions to roles
        $adminRole->permissions()->sync([$permission_1->id]);
        $moderatorRole->permissions()->sync([$permission_2->id]);

        // Attach the admin role to the user
        $created_user->roles()->sync([$adminRole->id]);
        $created_user->getRoleChecker()->clearCache();
        $created_user->getPermissionChecker()->clearCache();

        // Check permissions
        $found_user = (new User())->find($created_user->id);
        $this->assertTrue($found_user->hasPermission('test_permission_1'));
        $this->assertFalse($found_user->hasPermission('test_permission_2'));

        // Attach the moderator role with test_permission_2
        $created_user->roles()->sync([$adminRole->id, $moderatorRole->id]);
        $created_user->getRoleChecker()->clearCache();
        $created_user->getPermissionChecker()->clearCache();

        // Check the test_permission_2 permission
        $found_user = (new User())->find($created_user->id);
        $this->assertTrue($found_user->hasPermission('test_permission_1'));
        $this->assertTrue($found_user->hasPermission('test_permission_2'));
    }
}
