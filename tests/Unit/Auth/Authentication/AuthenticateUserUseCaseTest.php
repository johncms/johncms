<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authentication;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authentication\AuthenticateUserUseCase;
use Johncms\Auth\Authentication\LoginCaptcha;
use Johncms\Auth\Authentication\LoginCredentialsDTO;
use Johncms\Auth\Authentication\LoginStatus;
use Johncms\Config\ConfigRepository;
use Johncms\Http\Session;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\BootsInMemoryDatabase;

final class AuthenticateUserUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const PASSWORD = 'correct horse';

    private LoginCaptcha $captcha;

    private AuthenticateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->createUsersTable();

        ConfigRepository::init(['johncms' => ['user_email_confirmation' => 0]]);

        $this->captcha = new LoginCaptcha(new Session(new MockArraySessionStorage()));
        $this->useCase = new AuthenticateUserUseCase($this->captcha);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testCorrectCredentialsSignIn(): void
    {
        $user = $this->createUser();

        $result = $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD));

        self::assertSame(LoginStatus::Success, $result->status);
        self::assertTrue($result->isSuccessful());
        self::assertSame($user->id, $result->userId);
    }

    public function testTheLoginIsMatchedByItsLatinisedForm(): void
    {
        $this->createUser();

        // The stored name_lat is 'tester'; the visitor may type it in any case.
        self::assertTrue($this->useCase->execute(new LoginCredentialsDTO('TESTER', self::PASSWORD))->isSuccessful());
    }

    /**
     * An unknown login and a wrong password must be indistinguishable, or the form becomes a
     * way of finding out which accounts exist.
     */
    public function testAnUnknownLoginAnswersLikeAWrongPassword(): void
    {
        $this->createUser();

        self::assertSame(
            LoginStatus::InvalidCredentials,
            $this->useCase->execute(new LoginCredentialsDTO('nobody', self::PASSWORD))->status
        );
        self::assertSame(
            LoginStatus::InvalidCredentials,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', 'wrong'))->status
        );
    }

    public function testAnEmptyLoginIsRejectedWithoutAQuery(): void
    {
        self::assertSame(
            LoginStatus::InvalidCredentials,
            $this->useCase->execute(new LoginCredentialsDTO('', self::PASSWORD))->status
        );
    }

    public function testFailedAttemptsAreCountedUpToTheThreshold(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->useCase->execute(new LoginCredentialsDTO('Tester', 'wrong'));
        }

        // Past the threshold the form asks for a code anyway; a climbing number would mean nothing.
        self::assertSame(3, $this->reload($user)->failed_login);
    }

    public function testAfterThreeFailuresAVerificationCodeIsAskedFor(): void
    {
        $this->createUser(failedLogin: 3);

        self::assertSame(
            LoginStatus::CaptchaRequired,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->status
        );
    }

    public function testAWrongVerificationCodeStopsTheAttempt(): void
    {
        $this->createUser(failedLogin: 3);
        $this->captcha->issue();

        self::assertSame(
            LoginStatus::CaptchaMismatch,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, 'nope'))->status
        );
    }

    public function testTheRightVerificationCodeLetsTheAttemptThrough(): void
    {
        $this->createUser(failedLogin: 3);
        $code = $this->captcha->issue();

        self::assertTrue(
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, $code))->isSuccessful()
        );
    }

    /**
     * The picture is spent on the first answer, so a wrong guess cannot be retried against the
     * same code.
     */
    public function testAVerificationCodeIsGoodForOneAnswer(): void
    {
        $this->createUser(failedLogin: 3);
        $code = $this->captcha->issue();

        $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, 'nope'));

        self::assertSame(
            LoginStatus::CaptchaMismatch,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, $code))->status
        );
    }

    public function testASuccessfulSignInClearsTheFailureCount(): void
    {
        $user = $this->createUser(failedLogin: 2);

        $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD));

        self::assertSame(0, $this->reload($user)->failed_login);
    }

    public function testAnAccountAwaitingApprovalIsNamedButNotSignedIn(): void
    {
        $user = $this->createUser(approved: false);

        $result = $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD));

        self::assertSame(LoginStatus::ModerationPending, $result->status);
        self::assertSame($user->id, $result->userId);
    }

    public function testAnUnconfirmedAddressStopsTheSignInWhenTheSiteRequiresOne(): void
    {
        ConfigRepository::init(['johncms' => ['user_email_confirmation' => 1]]);
        $this->createUser(emailConfirmed: false);

        self::assertSame(
            LoginStatus::EmailNotConfirmed,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->status
        );
    }

    public function testAnUnconfirmedAddressIsIgnoredWhenTheSiteDoesNotRequireOne(): void
    {
        $this->createUser(emailConfirmed: false);

        self::assertTrue($this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->isSuccessful());
    }

    private function createUser(
        bool $approved = true,
        bool $emailConfirmed = true,
        int $failedLogin = 0,
    ): User {
        return User::query()->create(
            [
                'name'            => 'Tester',
                'name_lat'        => 'tester',
                'password'        => md5(md5(self::PASSWORD)),
                'preg'            => $approved,
                'email_confirmed' => $emailConfirmed,
                'failed_login'    => $failedLogin,
                'sestime'         => 0,
            ]
        );
    }

    private function reload(User $user): User
    {
        return User::query()->findOrFail($user->id);
    }

    /**
     * Only the columns this use case reads and writes: the real table is defined for the
     * installer, and dragging all sixty of its columns in here would say nothing.
     */
    private function createUsersTable(): void
    {
        Capsule::schema()->create(
            'users',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('name')->default('');
                $table->string('name_lat')->default('');
                $table->string('password')->default('');
                $table->integer('failed_login')->default(0);
                $table->boolean('preg')->default(true);
                $table->boolean('email_confirmed')->default(true);
                $table->integer('sestime')->default(0);
            }
        );
    }
}
