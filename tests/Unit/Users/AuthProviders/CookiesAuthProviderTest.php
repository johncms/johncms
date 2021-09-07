<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Tests\Unit\Users\AuthProviders;

use Johncms\Users\AuthProviders\CookiesAuthProvider;
use Johncms\Users\StoredAuth;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class CookiesAuthProviderTest extends AbstractTestCase
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

        // Store auth
        $cookiesAuthProvider = di(CookiesAuthProvider::class);
        $cookiesAuthProvider->store($user);

        $countAuth = (new StoredAuth())->where('user_id', $user->id)->count();
        $this->assertEquals(1, $countAuth);

        $cookiesAuthProvider->forgetAll($user);

        $countAuth = (new StoredAuth())->where('user_id', $user->id)->count();
        $this->assertEquals(0, $countAuth);
    }
}
