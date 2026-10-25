<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\AuthTables;
use Johncms\Auth\Authorization\UserRole;
use Johncms\Auth\External\UserIdentity;
use Johncms\Auth\Password\PasswordResetToken;
use Johncms\Auth\Session\AuthSession;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Deleting an account is confirmed in a dialog: the modal of the panel gets the dialog alone, a
 * plain visit gets it as a page, and the form returns to the page of the list it came from.
 */
final class DeleteUserDialogTest extends FunctionalTestCase
{
    public function testTheModalIsAnsweredWithTheDialogAlone(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Lotho']);

        $response = $this->handleRequest(
            '/admin/users/' . $member->id . '/delete?back=' . rawurlencode('/admin/users?q=Lotho'),
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/users/' . $member->id . '/delete">', trim($body));
        self::assertStringContainsString('name="back" value="/admin/users?q=Lotho"', $body);
        self::assertStringNotContainsString('<html', $body);
    }

    public function testAPlainVisitGetsTheDialogAsAPage(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->handleRequest(
            '/admin/users/' . $member->id . '/delete',
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        $body = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('class="modal admin-dialog-page"', $body);
        // Opened from the page of the account, closing leads back there: the profile of the site
        // belongs to a module that may be switched off.
        self::assertStringContainsString('href="/admin/users/' . $member->id . '" class="btn-close"', $body);
    }

    public function testTheFormReturnsToThePageOfTheListItCameFrom(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->delete($member, '/admin/users?q=Tester&page=2');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/users?q=Tester&page=2', $response->headers->get('Location'));
        self::assertNull(User::query()->find($member->id));
    }

    public function testWhatTheAccountHeldInTheAuthTablesGoesWithIt(): void
    {
        $member = FunctionalUserFactory::create();
        FunctionalUserFactory::grant($member->id, FunctionalUserFactory::createRole([], 5)->slug);
        Fixture::insert(AuthTables::USER_IDENTITIES, ['user_id' => $member->id, 'provider' => 'test', 'provider_user_id' => 'deleted-' . $member->id]);
        Fixture::insert(AuthTables::AUTH_SESSIONS, ['user_id' => $member->id, 'token_hash' => hash('sha256', 'session-' . $member->id)]);
        Fixture::insert(AuthTables::PASSWORD_RESET_TOKENS, ['user_id' => $member->id, 'token_hash' => hash('sha256', 'reset-' . $member->id)]);

        $this->delete($member, '/admin/users');

        self::assertNull(User::query()->find($member->id));
        self::assertSame(0, UserRole::query()->where('user_id', '=', $member->id)->count());
        self::assertSame(0, UserIdentity::query()->where('user_id', '=', $member->id)->count());
        self::assertSame(0, AuthSession::query()->where('user_id', '=', $member->id)->count());
        self::assertSame(0, PasswordResetToken::query()->where('user_id', '=', $member->id)->count());
    }

    public function testAReturnAddressOutsideTheListIsNotFollowed(): void
    {
        foreach (['https://example.com/admin/users', '//example.com/admin/users', '/admin/usersX', '/profile/1'] as $back) {
            $response = $this->delete(FunctionalUserFactory::create(), $back);

            self::assertSame('/admin/users', $response->headers->get('Location'), $back);
        }
    }

    public function testTheListOpensTheDeletionInTheDialog(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Bilbo']);

        $response = $this->handleRequest(
            '/admin/users?q=Bilbo',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertStringContainsString(
            'href="/admin/users/' . $member->id . '/delete?back=' . htmlspecialchars(rawurlencode('/admin/users?q=Bilbo')) . '" data-admin-modal',
            (string) $response->getContent()
        );
    }

    private function delete(User $member, string $back): Response
    {
        return $this->postForm('/admin/users/' . $member->id . '/delete', ['back' => $back], FunctionalUserFactory::createSupervisor());
    }
}
