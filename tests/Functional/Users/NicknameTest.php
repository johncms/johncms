<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\Modules\Auth\Application\DTO\SendRecoveryCommand;
use Johncms\Modules\Auth\Application\Exceptions\PasswordRecoveryException;
use Johncms\Modules\Auth\Application\UseCases\SendPasswordRecoveryUseCase;
use Johncms\Users\LoginName;
use Johncms\Users\User;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * A nickname reads the same wherever it is typed: it is checked by the same rules wherever it is
 * chosen, and names the same account at the sign-in and at the recovery of a password.
 */
final class NicknameTest extends FunctionalTestCase
{
    public function testANicknameOfNothingButSymbolsIsRefused(): void
    {
        $errors = $this->errorsOf('!!!');

        self::assertArrayHasKey('name', $errors);
        self::assertArrayNotHasKey('name_lat', $errors, 'The error is told at the field the visitor typed.');
    }

    public function testANicknameLeavingTheLoginNameOfAnotherAccountIsRefused(): void
    {
        FunctionalUserFactory::create(['name' => 'Frodo Baggins', 'name_lat' => 'frodo_baggins']);

        self::assertArrayHasKey('name', $this->errorsOf('frodo-baggins'));
        self::assertSame([], $this->errorsOf('Frodo Gamgee'));
    }

    public function testTheRecoveryOfAPasswordFindsTheAccountTheWayTheSignInDoes(): void
    {
        FunctionalUserFactory::create(['name' => 'Frodo Baggins', 'name_lat' => 'frodo_baggins', 'mail' => 'frodo@example.com']);
        $recovery = $this->container()->get(SendPasswordRecoveryUseCase::class);

        $recovery->execute(new SendRecoveryCommand('frodo baggins', 'frodo@example.com'), 'https://example.com');

        $this->expectException(PasswordRecoveryException::class);
        $recovery->execute(new SendRecoveryCommand('Samwise', 'frodo@example.com'), 'https://example.com');
    }

    public function testAnAccountWithAnOldLoginNameRecoversItsPasswordByItsNickname(): void
    {
        $user = FunctionalUserFactory::create(['name' => 'Вася Пупкин', 'name_lat' => 'vasya pupkin', 'mail' => 'vasya@example.com']);

        $this->container()->get(SendPasswordRecoveryUseCase::class)
            ->execute(new SendRecoveryCommand('Вася Пупкин', 'vasya@example.com'), 'https://example.com');

        self::assertSame('vasya pupkin', User::query()->findOrFail($user->id)->name_lat, 'Only signing in renews the login name.');
    }

    public function testRenamingAnAccountInItsProfileRenamesItsLogin(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Smeagol', 'name_lat' => 'smeagol']);
        $supervisor = FunctionalUserFactory::createSupervisor();

        // Without a confirmation of addresses the profile may be saved without one.
        $response = $this->withConfig(['johncms' => ['user_email_confirmation' => 0]], fn (): Response => $this->postForm(
            '/profile/' . $member->id . '/edit',
            ['name' => 'Rock & Roll', 'sex' => 'm', 'imname' => 'Smeagol', 'dayb' => '1', 'monthb' => '1', 'yearofbirth' => '1990'],
            $supervisor
        ));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $member = User::query()->findOrFail($member->id);
        // The nickname is kept as typed, and the login name is made of it, not of its markup.
        self::assertSame('Rock & Roll', $member->name);
        self::assertSame('rock_roll', $member->name_lat);
    }

    /**
     * @return array<string, list<string>>
     */
    private function errorsOf(string $nickname): array
    {
        $result = $this->container()->get(ValidatorInterface::class)
            ->validate(['name' => $nickname, 'name_lat' => LoginName::of($nickname)], LoginName::rules());

        return LoginName::errorsAtNickname($result)->getErrors();
    }
}
