<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Http\Request;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds the current-user service a test needs, signed in as whoever it asks for.
 *
 * The identity is put in through the authenticator chain, the same way the site does it, and the
 * profile comes from a repository the test controls — so a unit test needs neither a container
 * nor a database.
 */
final class CurrentUserFactory
{
    public static function guest(): CurrentUser
    {
        return self::for(Identity::guest());
    }

    /**
     * @param User|null $profile The row behind the identity, when the code under test reads the
     *                           profile fields. Without it the current user is an empty model.
     */
    public static function for(Identity $identity, ?User $profile = null): CurrentUser
    {
        $users = $profile === null
            ? new FakeUserRepository()
            : new FakeUserRepository([$profile]);

        return self::withRepository($identity, $users);
    }

    /**
     * Signed in as the owner of the profile: the id of the model is the id of the identity, and
     * an in-memory model built without one gets the first id.
     */
    public static function withProfile(User $profile): CurrentUser
    {
        $id = (int) $profile->id;

        if ($id === 0) {
            $id = 1;
            $profile->forceFill(['id' => $id]);
        }

        return self::for(IdentityFactory::user(id: $id), $profile);
    }

    /**
     * The same as for(), with the profile coming from a repository of the test's own — a real
     * one over an in-memory schema, for instance.
     */
    public static function withRepository(Identity $identity, UserRepositoryInterface $users): CurrentUser
    {
        $stack = new RequestStack();
        $stack->push(Request::create('/'));

        return new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity)]),
            new PermissionResolver(new FakeRoleRepository()),
            $stack,
            $users
        );
    }
}
