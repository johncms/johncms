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

use Carbon\Carbon;
use Johncms\Users\Ban\UserBan;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Tests\AbstractTestCase;

class UserBanCheckerTest extends AbstractTestCase
{
    public UserManager $userManager;

    protected function setUp(): void
    {
        $this->dropTables();
        $this->runMigrations();
        $this->userManager = di(UserManager::class);
        parent::setUp();
    }

    /**
     * @covers \Johncms\Users\User::hasBan
     * @covers \Johncms\Users\Ban\UserBanChecker::hasBan
     * @covers \Johncms\Users\User::getUserBanChecker
     */
    public function testHasBan()
    {
        // Create users
        $firstUser = $this->userManager->create(
            [
                'login'    => $this->faker->userName(),
                'email'    => $this->faker->email(),
                'phone'    => $this->faker->phoneNumber(),
                'password' => $this->faker->password(),
            ]
        );
        $secondUser = $this->userManager->create(
            [
                'login'    => $this->faker->userName(),
                'email'    => $this->faker->email(),
                'phone'    => $this->faker->phoneNumber(),
                'password' => $this->faker->password(),
            ]
        );

        // Check ban
        $this->assertFalse($secondUser->hasBan('full'));

        // Create ban
        UserBan::query()->create(
            [
                'active_from'  => Carbon::now(),
                'active_to'    => Carbon::now()->addDay(),
                'user_id'      => $secondUser->id,
                'type'         => 'full',
                'banned_by_id' => $firstUser->id,
                'reason'       => 'test',
            ]
        );

        // Check ban again
        $secondUser = User::query()->find($secondUser->id);
        $this->assertTrue($secondUser->hasBan('full'));
    }
}
