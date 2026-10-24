<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Security\ClientInfoDTO;
use Johncms\Users\Bans\BanKind;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Changing an account from the panel: the profile as it was typed, the password with the sessions
 * of the account, the settings — each for whoever holds the permission and does not reach up.
 */
final class UserEditTest extends FunctionalTestCase
{
    public function testTheProfileIsSavedAsItWasTyped(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Smeagol']);

        $response = $this->post('/admin/users/' . $member->id . '/edit', $this->profile(['name' => 'Gollum', 'live' => 'Rock & Roll <town>']));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $member = User::query()->findOrFail($member->id);
        self::assertSame('Rock & Roll <town>', $member->live);
        self::assertSame('Gollum', $member->name);
        // The account signs in under the login name, which follows the nickname.
        self::assertSame('gollum', $member->name_lat);
    }

    public function testATakenNicknameIsRefused(): void
    {
        FunctionalUserFactory::create(['name' => 'Aragorn']);
        $member = FunctionalUserFactory::create(['name' => 'Strider']);

        $response = $this->post('/admin/users/' . $member->id . '/edit', $this->profile(['name' => 'Aragorn']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('id="user-name" name="name" value="Aragorn"', (string) $response->getContent());
        self::assertSame('Strider', User::query()->findOrFail($member->id)->name);
    }

    public function testATakenEmailIsRefused(): void
    {
        FunctionalUserFactory::create(['mail' => 'frodo@example.com']);
        $member = FunctionalUserFactory::create(['mail' => 'sam@example.com']);

        $response = $this->post('/admin/users/' . $member->id . '/edit', $this->profile(['name' => $member->name, 'mail' => 'frodo@example.com']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('sam@example.com', User::query()->findOrFail($member->id)->mail);
    }

    public function testTheNotesAreChangedOnlyBySomebodyWhoMayReadThem(): void
    {
        $editor = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_PROFILE_EDIT], level: 20);
        $member = FunctionalUserFactory::create(['admin_notes' => 'Keeps arguing in the guestbook']);

        $form = (string) $this->handleRequest('/admin/users/' . $member->id . '/edit', cookies: $this->actingAs($editor))->getContent();
        self::assertStringNotContainsString('name="admin_notes"', $form);

        $this->handleRequest(
            '/admin/users/' . $member->id . '/edit',
            'POST',
            $this->withCsrfToken($this->profile(['name' => $member->name, 'admin_notes' => 'Nothing to see'])),
            cookies: $this->actingAs($editor)
        );

        self::assertSame('Keeps arguing in the guestbook', User::query()->findOrFail($member->id)->admin_notes);
    }

    public function testAnEditorBannedFromTheSiteChangesNothing(): void
    {
        $editor = FunctionalUserFactory::createSupervisor();
        Fixture::insert('cms_ban_users', ['user_id' => $editor->id, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_type' => BanKind::SITE->value, 'ban_reason' => 'Spam', 'ban_who' => 'admin']);
        $member = FunctionalUserFactory::create(['name' => 'Bill']);

        $this->handleRequest(
            '/admin/users/' . $member->id . '/edit',
            'POST',
            $this->withCsrfToken($this->profile(['name' => 'Ferny'])),
            cookies: $this->actingAs($editor)
        );

        self::assertSame('Bill', User::query()->findOrFail($member->id)->name);
    }

    public function testABirthdayIsAllThreePartsOrNone(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->post('/admin/users/' . $member->id . '/edit', $this->profile(['dayb' => '5']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(0, (int) User::query()->findOrFail($member->id)->dayb);
    }

    public function testAnEditorWithoutThePermissionMayNotOpenSomebodyElse(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $member = FunctionalUserFactory::create();

        $response = $this->handleRequest('/admin/users/' . $member->id . '/edit', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testNobodyChangesAnAccountStandingAboveThem(): void
    {
        $editor = FunctionalUserFactory::createWithPermissions(
            [CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_PROFILE_EDIT, CorePermissions::USERS_PASSWORD_CHANGE],
            level: 20
        );
        $supervisor = FunctionalUserFactory::createSupervisor();
        $cookies = $this->actingAs($editor);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->handleRequest('/admin/users/' . $supervisor->id . '/edit', cookies: $cookies)->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $this->handleRequest('/admin/users/' . $supervisor->id . '/password', cookies: $cookies)->getStatusCode());
    }

    public function testTheFieldsOfTheStaffAreNotTheOwnersToChange(): void
    {
        $owner = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], ['name' => 'Bilbo']);

        $this->handleRequest(
            '/admin/users/' . $owner->id . '/edit',
            'POST',
            $this->withCsrfToken($this->profile(['name' => 'Baggins', 'karma_off' => '1', 'live' => 'Bag End'])),
            cookies: $this->actingAs($owner)
        );

        $owner = User::query()->findOrFail($owner->id);
        self::assertSame('Bilbo', $owner->name);
        self::assertFalse($owner->karma_off);
        self::assertSame('Bag End', $owner->live);
    }

    public function testANewPasswordSignsTheAccountOutEverywhere(): void
    {
        $member = FunctionalUserFactory::create();
        $sessions = $this->container()->get(AuthSessionManager::class);
        $sessions->start($member->id, true, new ClientInfoDTO('127.0.0.1', '', 'phpunit'));

        $response = $this->post('/admin/users/' . $member->id . '/password', ['password' => 'new-secret', 'confirmation' => 'new-secret', 'sign_out' => '1']);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue($this->container()->get(PasswordHasherInterface::class)->verify('new-secret', User::query()->findOrFail($member->id)->password));
        self::assertCount(0, $sessions->activeFor($member->id));
    }

    public function testTheOwnerChangesTheirOwnPasswordAndStaysSignedInHere(): void
    {
        $owner = FunctionalUserFactory::createSupervisor();
        $sessions = $this->container()->get(AuthSessionManager::class);
        $elsewhere = $sessions->start($owner->id, true, new ClientInfoDTO('10.0.0.1', '', 'phpunit'));
        $cookies = $this->actingAs($owner);
        $uri = '/admin/users/' . $owner->id . '/password';

        $response = $this->handleRequest(
            $uri,
            'POST',
            $this->withCsrfToken(['password' => 'new-secret', 'confirmation' => 'new-secret', 'sign_out' => '1']),
            cookies: $cookies
        );

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertTrue($this->container()->get(PasswordHasherInterface::class)->verify('new-secret', User::query()->findOrFail($owner->id)->password));
        // Signed out on the other device, and still in on this one.
        self::assertNull($sessions->find($elsewhere->token));
        self::assertSame(Response::HTTP_OK, $this->handleRequest($uri, cookies: $cookies)->getStatusCode());
    }

    public function testAMistypedConfirmationKeepsTheOldPassword(): void
    {
        $member = FunctionalUserFactory::create();
        $old = $member->password;

        $response = $this->post('/admin/users/' . $member->id . '/password', ['password' => 'new-secret', 'confirmation' => 'new-secreT']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($old, User::query()->findOrFail($member->id)->password);
    }

    public function testAShortPasswordIsRefused(): void
    {
        $member = FunctionalUserFactory::create();

        $response = $this->post('/admin/users/' . $member->id . '/password', ['password' => 'short', 'confirmation' => 'short']);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($member->password, User::query()->findOrFail($member->id)->password);
    }

    public function testThePasswordOfSomebodyElseNeedsThePermission(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_PROFILE_EDIT], level: 20);
        $member = FunctionalUserFactory::create();

        $response = $this->handleRequest('/admin/users/' . $member->id . '/password', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * Wiping the settings of a peer is not what the permission is for: it reaches down only.
     */
    public function testTheSettingsOfAPeerAreNotReset(): void
    {
        $permissions = [CorePermissions::ADMIN_ACCESS, CorePermissions::USERS_SETTINGS_RESET];
        $editor = FunctionalUserFactory::createWithPermissions($permissions, level: 20);
        $peer = FunctionalUserFactory::createWithPermissions($permissions, level: 20);
        $peer->update(['set_forum' => ['upfp' => 1]]);

        $response = $this->handleRequest('/admin/users/' . $peer->id . '/reset-settings', 'POST', $this->withCsrfToken(), cookies: $this->actingAs($editor));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(['upfp' => 1], User::query()->findOrFail($peer->id)->set_forum);
    }

    public function testTheSettingsGoBackToTheDefaults(): void
    {
        $member = FunctionalUserFactory::create();
        $member->update(['set_forum' => ['upfp' => 1]]);

        $response = $this->post('/admin/users/' . $member->id . '/reset-settings', []);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame([], User::query()->findOrFail($member->id)->set_forum);
    }

    /**
     * @param array<string, string> $changes
     * @return array<string, string>
     */
    private function profile(array $changes): array
    {
        return $changes + [
            'name'   => 'Tester' . random_int(1000, 9999),
            'sex'    => 'm',
            'mail'   => '',
            'imname' => '',
            'live'   => '',
        ];
    }

    /**
     * @param array<string, string> $parameters
     */
    private function post(string $uri, array $parameters): Response
    {
        return $this->handleRequest(
            $uri,
            'POST',
            $this->withCsrfToken($parameters),
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
    }
}
