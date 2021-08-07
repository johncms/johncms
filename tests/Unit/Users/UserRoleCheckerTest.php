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

use Johncms\Users\Role;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class UserRoleCheckerTest extends AbstractTestCase
{
    public UserManager $userManager;

    public array $userFields = [];

    protected function setUp(): void
    {
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

    /**
     * @covers \Johncms\Users\User::hasRole
     * @covers \Johncms\Users\UserRoleChecker::hasRole
     */
    public function testHasRole()
    {
        // Test create
        $created_user = $this->userManager->create($this->userFields);
        $created_user->roles()->sync([1]);

        $found_user = (new User())->find($created_user->id);

        $this->assertTrue($found_user->hasRole('admin'));
        $this->assertFalse($found_user->hasRole('moderator'));
        $this->assertFalse($found_user->hasRole(['moderator', 'testRole']));
        $this->assertTrue($found_user->hasRole(['moderator', 'testRole', 'admin']));
    }
}
