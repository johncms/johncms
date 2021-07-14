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

use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class UserManagerTest extends AbstractTestCase
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
        parent::setUp();
    }

    /**
     * @covers \Johncms\Users\UserManager::create
     * @covers \Johncms\Users\UserManager::update
     */
    public function testCreate()
    {
        // Test create
        $created_user = $this->userManager->create($this->userFields);

        $found_user = (new User())->find($created_user->id);

        $this->assertEquals($this->userFields['login'], $found_user->login);
        $this->assertEquals($this->userFields['email'], $found_user->email);
        $this->assertEquals($this->userFields['phone'], $found_user->phone);
        $this->assertTrue(password_verify($this->userFields['password'], $found_user->password));

        // Test update
        $found_user = (new User())->where('login', $this->userFields['login'])->first();
        $this->assertNotNull($found_user);

        $new_fields = [
            'login'    => $this->faker->userName(),
            'email'    => $this->faker->email(),
            'phone'    => $this->faker->phoneNumber(),
            'password' => $this->faker->password(),
        ];

        $updated_user = $this->userManager->update($found_user->id, $new_fields);

        $this->assertEquals($new_fields['login'], $updated_user->login);
        $this->assertEquals($new_fields['email'], $updated_user->email);
        $this->assertEquals($new_fields['phone'], $updated_user->phone);
        $this->assertTrue(password_verify($new_fields['password'], $updated_user->password));
    }

    public function testExceptions()
    {
        try {
            $this->userManager->update(323232332, []);
        } catch (RuntimeException $runtimeException) {
            $userNotFoundExceptionMessage = $runtimeException->getMessage();
        }
        $this->assertEquals('The user with id 323232332 was not found', $userNotFoundExceptionMessage ?? null);

        try {
            $this->userManager->create([]);
        } catch (RuntimeException $runtimeException) {
            $missedLoginFieldExceptionMessage = $runtimeException->getMessage();
        }
        $this->assertEquals('The login field "login" must be in the fields array', $missedLoginFieldExceptionMessage ?? null);

        try {
            $this->userManager->create(['login' => 'login']);
        } catch (RuntimeException $runtimeException) {
            $missedPasswordFieldExceptionMessage = $runtimeException->getMessage();
        }
        $this->assertEquals('The password is not specified.', $missedPasswordFieldExceptionMessage ?? null);
    }
}
