<?php

declare(strict_types=1);

namespace Tests\Unit\System\Users;

use Johncms\System\Users\User;
use PHPUnit\Framework\TestCase;

/**
 * The legacy current-user service is shared and its state is replaced for every request
 * (CurrentUserAuthenticator), so what setProperties() leaves behind is what the next visitor
 * would be answered with.
 */
final class UserTest extends TestCase
{
    public function testDeclaredPropertiesGoBackToTheirDefault(): void
    {
        $user = new User(['id' => 7, 'name' => 'Visitor', 'rights' => 9, 'ban' => [1 => 1]]);

        $user->setProperties([]);

        self::assertSame(0, $user->id);
        self::assertSame('', $user->name);
        self::assertSame(0, $user->rights);
        self::assertSame([], $user->ban);
    }

    public function testDynamicPropertiesOfThePreviousVisitorAreRemoved(): void
    {
        // Columns the class does not declare arrive as dynamic properties, straight from the
        // users table — putting them back to a default is not possible, they have to go.
        $user = new User(['id' => 7, 'undeclared_column' => 'previous visitor']);

        $user->setProperties(['id' => 8]);

        self::assertSame(8, $user->id);
        self::assertFalse(property_exists($user, 'undeclared_column'));
    }

    public function testUserConfigIsRebuiltFromTheNewProperties(): void
    {
        $user = new User(['set_user' => serialize(['lng' => 'ru'])]);
        self::assertSame('ru', $user->config->lng);

        $user->setProperties(['set_user' => serialize(['lng' => 'en'])]);

        self::assertSame('en', $user->config->lng);
    }
}
