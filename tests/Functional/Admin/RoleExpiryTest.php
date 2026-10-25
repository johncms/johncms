<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * A role handed out until a date opens the panel until that date, and not after it: the grant is
 * read on every request, so nobody has to take the role back by hand.
 */
final class RoleExpiryTest extends FunctionalTestCase
{
    public function testTheRoleOpensThePanelUntilItsGrantRunsOut(): void
    {
        $moderator = $this->moderatorUntil(time() + 3600);

        $response = $this->handleRequest('/admin', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTheRoleNoLongerOpensThePanelOnceItsGrantRanOut(): void
    {
        $moderator = $this->moderatorUntil(time() - 1);

        $response = $this->handleRequest('/admin', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    private function moderatorUntil(int $expiresAt): User
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 30);
        UserRole::query()->where('user_id', '=', $moderator->id)->update(['expires_at' => $expiresAt]);

        return $moderator;
    }
}
