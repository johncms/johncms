<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Tests\Unit\Users\AuthProviders;

use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class SessionAuthProviderTest extends AbstractTestCase
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

    public function testStore()
    {
        // Create new user
        $user = $this->userManager->create($this->userFields);

        // Store user
        $sessionAuthProvider = di(SessionAuthProvider::class);
        $sessionAuthProvider->store($user);

        // Try to authenticate stored user
        $authUser = $sessionAuthProvider->authenticate();
        $this->assertEquals($user->id, $authUser->id);

        // Forget stored user and try to authenticate
        $sessionAuthProvider->forget();
        $authUser = $sessionAuthProvider->authenticate();
        $this->assertNull($authUser);

        $user->delete();
    }

    public function testChangedPassword()
    {
        // Create new user
        $user = $this->userManager->create($this->userFields);

        // Store user
        $sessionAuthProvider = di(SessionAuthProvider::class);
        $sessionAuthProvider->store($user);

        // Try to authenticate stored user
        $authUser = $sessionAuthProvider->authenticate();
        $this->assertEquals($user->id, $authUser->id);

        $this->userManager->update($user->id, ['password' => $this->faker->password()]);

        // Try to authenticate stored user
        $authUser = $sessionAuthProvider->authenticate();
        $this->assertNull($authUser);
    }
}
