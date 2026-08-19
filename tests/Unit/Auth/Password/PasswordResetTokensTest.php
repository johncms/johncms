<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Password;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Infrastructure\Persistence\Repository\EloquentPasswordResetTokenRepository;
use Johncms\Auth\Password\PasswordResetToken;
use Johncms\Auth\Password\PasswordResetTokens;
use Johncms\Auth\AuthTables;
use Johncms\Auth\SecureToken;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class PasswordResetTokensTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private PasswordResetTokens $tokens;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_auth_schema');

        $this->tokens = new PasswordResetTokens(new EloquentPasswordResetTokenRepository());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheSchemaCanBeBuiltOnItsOwn(): void
    {
        self::assertTrue(Capsule::schema()->hasTable(AuthTables::PASSWORD_RESET_TOKENS));
    }

    public function testCreatingTheSchemaTwiceIsHarmless(): void
    {
        $this->migrate('system', 'initial_auth_schema');

        self::assertTrue(Capsule::schema()->hasTable(AuthTables::PASSWORD_RESET_TOKENS));
    }

    public function testAnIssuedTokenIdentifiesItsUser(): void
    {
        $token = $this->tokens->issue(7);

        self::assertSame(7, $this->tokens->verify($token));
    }

    /**
     * The value in the letter is the only copy. A dump of the table must not hand out working
     * links, which is what storing the digest buys.
     */
    public function testOnlyTheDigestIsStored(): void
    {
        $token = $this->tokens->issue(7);

        $stored = PasswordResetToken::query()->firstOrFail();

        self::assertNotSame($token, $stored->token_hash);
        self::assertSame(SecureToken::hash($token), $stored->token_hash);
    }

    public function testEveryTokenIsDifferent(): void
    {
        self::assertNotSame($this->tokens->issue(1), $this->tokens->issue(2));
    }

    public function testAnUnknownTokenBelongsToNobody(): void
    {
        $this->tokens->issue(7);

        self::assertNull($this->tokens->verify('not-a-real-token'));
        self::assertNull($this->tokens->verify(''));
    }

    public function testVerifyingDoesNotSpendTheToken(): void
    {
        $token = $this->tokens->issue(7);

        $this->tokens->verify($token);

        self::assertSame(7, $this->tokens->verify($token));
    }

    public function testATokenWorksOnce(): void
    {
        $token = $this->tokens->issue(7);

        self::assertSame(7, $this->tokens->consume($token));
        self::assertNull($this->tokens->consume($token));
        self::assertNull($this->tokens->verify($token));
    }

    public function testATokenExpires(): void
    {
        $now = time();
        $token = $this->tokens->issue(7, $now);

        self::assertSame(7, $this->tokens->verify($token, $now + PasswordResetTokens::TTL - 1));
        self::assertNull($this->tokens->verify($token, $now + PasswordResetTokens::TTL + 1));
    }

    /**
     * Otherwise every letter ever sent would stay valid for its hour, and asking again would
     * widen the window instead of replacing it.
     */
    public function testIssuingAgainInvalidatesThePreviousLink(): void
    {
        $now = time();
        $first = $this->tokens->issue(7, $now);
        $second = $this->tokens->issue(7, $now);

        self::assertNull($this->tokens->verify($first, $now));
        self::assertSame(7, $this->tokens->verify($second, $now));
    }

    public function testOneRequestPerDayPerAccount(): void
    {
        $now = time();
        $this->tokens->issue(7, $now);

        self::assertFalse($this->tokens->canRequest(7, $now));
        self::assertFalse($this->tokens->canRequest(7, $now + PasswordResetTokens::REQUEST_INTERVAL - 1));
        self::assertTrue($this->tokens->canRequest(7, $now + PasswordResetTokens::REQUEST_INTERVAL));
        // Another account is not affected by this one asking.
        self::assertTrue($this->tokens->canRequest(8, $now));
    }

    /**
     * The limit counts letters sent, not links followed: spending a token must not open the
     * door to asking for another one straight away.
     */
    public function testConsumingDoesNotResetTheRateLimit(): void
    {
        $now = time();
        $token = $this->tokens->issue(7, $now);
        $this->tokens->consume($token, $now);

        self::assertFalse($this->tokens->canRequest(7, $now));
    }

    public function testExpiredTokensCanBeCleanedUp(): void
    {
        $now = time();
        $this->tokens->issue(7, $now - PasswordResetTokens::TTL - 10);
        $this->tokens->issue(8, $now);

        $removed = (new EloquentPasswordResetTokenRepository())->deleteExpiredBefore($now);

        self::assertSame(1, $removed);
        self::assertSame(1, PasswordResetToken::query()->count());
    }
}
