<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The profiles are open to members only. A guest who follows an address of one is refused with a
 * 403 and pointed at the login form, not told that the profile does not exist.
 */
final class GuestAccessTest extends FunctionalTestCase
{
    public function testAGuestIsRefusedAndOfferedToSignIn(): void
    {
        $owner = FunctionalUserFactory::create();

        $response = $this->handleRequest('/profile/' . $owner->id);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringContainsString('href="/login/"', (string) $response->getContent());
    }

    public function testAMemberOpensTheProfile(): void
    {
        $owner = FunctionalUserFactory::create();

        $response = $this->handleRequest('/profile/' . $owner->id, cookies: $this->actingAs(FunctionalUserFactory::create()));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testASignedInVisitorIsNotOfferedToSignInOnARefusal(): void
    {
        $member = FunctionalUserFactory::create();
        $other = FunctionalUserFactory::create();

        $response = $this->handleRequest('/profile/' . $other->id . '/edit', cookies: $this->actingAs($member));

        self::assertNotSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString('href="/login/"', (string) $response->getContent());
    }
}
