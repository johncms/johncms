<?php

declare(strict_types=1);

namespace Tests\Functional\Auth;

use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Mail\EmailMessage;
use Johncms\Modules\Auth\Application\UseCases\CompletePasswordRecoveryUseCase;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The link of a recovery gives the account a new password, mailed to its owner.
 */
final class PasswordRecoveryTest extends FunctionalTestCase
{
    public function testTheNewPasswordIsAsLongAsRegistrationAsksAndSignsTheAccountIn(): void
    {
        $user = FunctionalUserFactory::create(['mail' => 'frodo@example.com']);
        $code = $this->container()->get(PasswordResetTokens::class)->issue($user->id);

        $this->container()->get(CompletePasswordRecoveryUseCase::class)->execute($user, $code);

        $password = (string) EmailMessage::query()->latest('id')->firstOrFail()->fields['user_password'];
        self::assertSame(6, strlen($password));
        self::assertTrue($this->container()->get(PasswordHasherInterface::class)->verify($password, User::query()->findOrFail($user->id)->password));
    }
}
