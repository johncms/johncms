<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\System\Users\User as LegacyUser;
use Johncms\Users\User;
use PDO;
use Tests\Functional\FunctionalTestCase;

/**
 * The two current-user services are shared, and hundreds of controllers take them in their
 * constructor — so they cannot be rebuilt per request, their state is replaced instead
 * (CurrentUserAuthenticator). This asserts both halves of that: the same object survives, and
 * what it holds belongs to the request being served rather than to the previous one.
 *
 * A worker runtime is where this matters; under FPM the process ends with the request. That is
 * also why the test drives two requests through one process instead of asserting on a page.
 */
final class CurrentUserPerRequestTest extends FunctionalTestCase
{
    private ?int $userId = null;

    protected function tearDown(): void
    {
        if ($this->userId !== null) {
            $this->container()->get(PDO::class)
                ->exec('DELETE FROM `users` WHERE `id` = ' . $this->userId);
            $this->userId = null;
        }

        parent::tearDown();
    }

    public function testTheVisitorOfEachRequestIsLoadedIntoTheSharedInstances(): void
    {
        $userId = $this->createUser();

        $user = $this->container()->get(User::class);
        $legacyUser = $this->container()->get(LegacyUser::class);

        $this->handleRequest('/', cookies: $this->actingAs($userId));

        self::assertSame($userId, $user->id, 'The Eloquent user must hold the visitor of the request.');
        self::assertTrue($user->exists);
        self::assertSame($userId, $legacyUser->id, 'The legacy user must hold the visitor of the request.');

        // A guest now: everything the previous visitor left in the shared instances has to go,
        // or the next request of a worker answers as them.
        $this->handleRequest('/');

        self::assertNull($user->id, 'The Eloquent user still holds the previous visitor.');
        self::assertFalse($user->exists);
        self::assertSame(0, $legacyUser->id, 'The legacy user still holds the previous visitor.');
        self::assertSame('', $legacyUser->name);
        self::assertSame([], $legacyUser->ban);
    }

    public function testTheSharedInstancesAreNeverReplaced(): void
    {
        $userId = $this->createUser();

        $user = $this->container()->get(User::class);
        $legacyUser = $this->container()->get(LegacyUser::class);

        $this->handleRequest('/', cookies: $this->actingAs($userId));

        // Replacing the objects would leave every service that took them in its constructor with
        // the user of the request the container was built for.
        self::assertSame($user, $this->container()->get(User::class));
        self::assertSame($legacyUser, $this->container()->get(LegacyUser::class));
    }

    /**
     * The visitor is stored with the loopback address, the one Environment falls back to when
     * there is no REMOTE_ADDR: the authentication would otherwise record an IP history entry.
     */
    private function createUser(): int
    {
        $db = $this->container()->get(PDO::class);
        $name = 'phpunit-per-request-' . bin2hex(random_bytes(4));

        $statement = $db->prepare(
            'INSERT INTO `users` SET
                `name` = :name, `name_lat` = :name_lat, `password` = :password,
                `imname` = "", `sex` = "m", `mail` = "", `skype` = "", `jabber` = "", `www` = "",
                `live` = "", `mibile` = "", `status` = "", `browser` = "", `regadm` = "",
                `rest_code` = "", `set_user` = "a:0:{}", `ip` = :ip, `ip_via_proxy` = 0,
                `preg` = 1, `mailvis` = 0,
                `dayb` = 0, `monthb` = 0, `karma_plus` = 0, `karma_minus` = 0, `karma_off` = 0,
                `failed_login` = 0, `datereg` = :datereg, `lastdate` = :lastdate, `email_confirmed` = 1'
        );

        $statement->execute(
            [
                'name'     => $name,
                'name_lat' => $name,
                'password' => md5(md5('per-request-user')),
                'ip'       => sprintf('%u', ip2long('127.0.0.1')),
                'datereg'  => time(),
                'lastdate' => time(),
            ]
        );

        $this->userId = (int) $db->lastInsertId();

        return $this->userId;
    }
}
