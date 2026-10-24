<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Session\AuthSessionManager;
use Johncms\Security\ClientInfoDTO;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
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
