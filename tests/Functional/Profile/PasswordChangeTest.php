<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The owner changes their password in the profile by the length registration asks for.
 */
final class PasswordChangeTest extends FunctionalTestCase
{
    public function testAPasswordShorterThanSixCharactersIsRefused(): void
    {
        $owner = FunctionalUserFactory::create();

        $this->change($owner, 'abcde');

        self::assertSame($owner->password, User::query()->findOrFail($owner->id)->password);
    }

    public function testAPasswordOfSixCharactersIsAccepted(): void
    {
        $owner = FunctionalUserFactory::create();

        $this->change($owner, 'abcdef');

        self::assertTrue($this->container()->get(PasswordHasherInterface::class)->verify('abcdef', User::query()->findOrFail($owner->id)->password));
    }

    private function change(User $owner, string $password): void
    {
        $this->handleRequest(
            '/profile/' . $owner->id . '/password',
            'POST',
            $this->withCsrfToken(['oldpass' => FunctionalUserFactory::PASSWORD, 'newpass' => $password, 'newconf' => $password]),
            cookies: $this->actingAs($owner)
        );
    }
}
