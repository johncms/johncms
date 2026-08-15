<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Authentication;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Auth\Authentication\AuthenticateUserUseCase;
use Johncms\Auth\Authentication\LoginCaptcha;
use Johncms\Auth\Authentication\LoginCredentialsDTO;
use Johncms\Auth\Authentication\LoginStatus;
use Johncms\Auth\Password\LegacyMd5PasswordVerifier;
use Johncms\Auth\Password\NativePasswordHasher;
use Johncms\Auth\Password\PasswordHasherInterface;
use Johncms\Auth\Throttling\CacheLoginThrottle;
use Johncms\Auth\Throttling\LoginThrottleInterface;
use Johncms\Config\ConfigRepository;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\BootsInMemoryDatabase;

final class AuthenticateUserUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const PASSWORD = 'correct horse';

    private LoginCaptcha $captcha;

    private PasswordHasherInterface $hasher;

    private LoginThrottleInterface $throttle;

    private AuthenticateUserUseCase $useCase;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->createUsersTable();

        ConfigRepository::init(['johncms' => ['user_email_confirmation' => 0]]);

        $this->captcha = new LoginCaptcha(new Session(new MockArraySessionStorage()));
        // The cheapest cost bcrypt accepts: these tests hash on every fixture, and the strength
        // of the algorithm is not what they are about.
        $this->hasher = new NativePasswordHasher(new LegacyMd5PasswordVerifier(), PASSWORD_BCRYPT, ['cost' => 4]);
        $this->throttle = new CacheLoginThrottle(new CacheRepository(new ArrayStore()));

        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/'));

        $this->useCase = new AuthenticateUserUseCase(
            $this->captcha,
            $this->hasher,
            $this->throttle,
            new Environment($requestStack)
        );
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

    public function testAfterThreeFailuresAVerificationCodeIsAskedFor(): void
    {
        $this->createUser();
        $this->failTimes(3);

        self::assertSame(
            LoginStatus::CaptchaRequired,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->status
        );
    }

    /**
     * The failures are counted against the attempt, not against the account, so a guesser who
     * never names an existing login is slowed down just the same.
     */
    public function testFailuresAgainstAnUnknownLoginCountToo(): void
    {
        $this->createUser();

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->useCase->execute(new LoginCredentialsDTO('nobody', 'wrong'));
        }

        // Same address, a different login: the address key has run out on its own.
        self::assertSame(
            LoginStatus::CaptchaRequired,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->status
        );
    }

    public function testAWrongVerificationCodeStopsTheAttempt(): void
    {
        $this->createUser();
        $this->failTimes(3);
        $this->captcha->issue();

        self::assertSame(
            LoginStatus::CaptchaMismatch,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, 'nope'))->status
        );
    }

    public function testTheRightVerificationCodeLetsTheAttemptThrough(): void
    {
        $this->createUser();
        $this->failTimes(3);
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
        $this->createUser();
        $this->failTimes(3);
        $code = $this->captcha->issue();

        $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, 'nope'));

        self::assertSame(
            LoginStatus::CaptchaMismatch,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, $code))->status
        );
    }

    public function testASuccessfulSignInForgetsTheFailures(): void
    {
        $this->createUser();
        $this->failTimes(3);

        $code = $this->captcha->issue();
        $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD, $code));

        // No verification code asked for this time: the run of failures is gone.
        self::assertTrue($this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->isSuccessful());
    }

    /**
     * A verification code alone is no protection against a script that solves them: past a
     * further threshold the attempts are simply refused for a while.
     */
    public function testEnoughFailuresRefuseAttemptsAltogether(): void
    {
        $this->createUser();
        $this->failTimes(10);

        $result = $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD));

        self::assertSame(LoginStatus::TooManyAttempts, $result->status);
        self::assertGreaterThan(0, $result->retryAfter);
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

    /**
     * Upgrading a site must not force everyone to reset their password: the old scheme keeps
     * working until its owner comes back.
     */
    public function testAPasswordStoredInTheOldSchemeStillSignsIn(): void
    {
        $this->createUser(storedHash: md5(md5(self::PASSWORD)));

        self::assertTrue($this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD))->isSuccessful());
    }

    /**
     * Signing in is the only moment the password exists in the clear, so it is the only moment
     * an outdated hash can be replaced.
     */
    public function testSigningInReplacesAnOutdatedHash(): void
    {
        $legacyHash = md5(md5(self::PASSWORD));
        $user = $this->createUser(storedHash: $legacyHash);

        $this->useCase->execute(new LoginCredentialsDTO('Tester', self::PASSWORD));

        $stored = $this->reload($user)->password;
        self::assertNotSame($legacyHash, $stored);
        self::assertTrue($this->hasher->verify(self::PASSWORD, $stored));
        // And the replacement is good enough that it is not replaced again next time.
        self::assertFalse($this->hasher->needsRehash($stored));
    }

    public function testAWrongPasswordAgainstAnOldHashDoesNotSignIn(): void
    {
        $this->createUser(storedHash: md5(md5(self::PASSWORD)));

        self::assertSame(
            LoginStatus::InvalidCredentials,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', 'wrong'))->status
        );
    }

    /**
     * An account created through an external service has no password at all, and an empty
     * column must never be a way in.
     */
    public function testAnAccountWithoutAPasswordCannotBeSignedInto(): void
    {
        $this->createUser(storedHash: '');

        self::assertSame(
            LoginStatus::InvalidCredentials,
            $this->useCase->execute(new LoginCredentialsDTO('Tester', ''))->status
        );
    }

    private function createUser(
        bool $approved = true,
        bool $emailConfirmed = true,
        ?string $storedHash = null,
    ): User {
        $user = new User();
        $user->fill(
            [
                'name'            => 'Tester',
                'name_lat'        => 'tester',
                'preg'            => $approved,
                'email_confirmed' => $emailConfirmed,
                'sestime'         => 0,
            ]
        );
        $user->password = $storedHash ?? $this->hasher->hash(self::PASSWORD);
        $user->save();

        return $user;
    }

    /**
     * Fails the sign-in the given number of times, answering the verification code correctly
     * whenever it is asked for, so the run is not cut short by it.
     */
    private function failTimes(int $times): void
    {
        for ($attempt = 0; $attempt < $times; $attempt++) {
            $this->useCase->execute(new LoginCredentialsDTO('Tester', 'wrong', $this->captcha->issue()));
        }
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
                $table->boolean('preg')->default(true);
                $table->boolean('email_confirmed')->default(true);
                $table->integer('sestime')->default(0);
            }
        );
    }
}
