<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\Password;

use Johncms\Auth\Password\LegacyMd5PasswordVerifier;
use Johncms\Auth\Password\NativePasswordHasher;
use PHPUnit\Framework\TestCase;

final class NativePasswordHasherTest extends TestCase
{
    private const PASSWORD = 'correct horse';

    public function testHashingIsSaltedSoTwoAccountsNeverShareAValue(): void
    {
        $hasher = $this->hasher();

        self::assertNotSame($hasher->hash(self::PASSWORD), $hasher->hash(self::PASSWORD));
    }

    public function testAHashVerifiesAgainstItsPassword(): void
    {
        $hasher = $this->hasher();

        self::assertTrue($hasher->verify(self::PASSWORD, $hasher->hash(self::PASSWORD)));
        self::assertFalse($hasher->verify('wrong', $hasher->hash(self::PASSWORD)));
    }

    public function testAFreshHashDoesNotNeedRedoing(): void
    {
        $hasher = $this->hasher();

        self::assertFalse($hasher->needsRehash($hasher->hash(self::PASSWORD)));
    }

    public function testTheOldSchemeIsStillAccepted(): void
    {
        self::assertTrue($this->hasher()->verify(self::PASSWORD, md5(md5(self::PASSWORD))));
        self::assertFalse($this->hasher()->verify('wrong', md5(md5(self::PASSWORD))));
    }

    public function testTheOldSchemeIsAlwaysDueForReplacement(): void
    {
        self::assertTrue($this->hasher()->needsRehash(md5(md5(self::PASSWORD))));
    }

    /**
     * Raising the cost has to reach the accounts already signed up, and the only signal for
     * that is needsRehash() on the next successful sign-in.
     */
    public function testWeakerParametersAreDueForReplacement(): void
    {
        $weak = new NativePasswordHasher(new LegacyMd5PasswordVerifier(), PASSWORD_BCRYPT, ['cost' => 4]);
        $strong = new NativePasswordHasher(new LegacyMd5PasswordVerifier(), PASSWORD_BCRYPT, ['cost' => 6]);

        self::assertTrue($strong->needsRehash($weak->hash(self::PASSWORD)));
        // And a value made by the stronger settings still verifies under either of them.
        self::assertTrue($weak->verify(self::PASSWORD, $strong->hash(self::PASSWORD)));
    }

    /**
     * An account created through an external service has no password. An empty column must not
     * be a way in, whatever is submitted against it.
     */
    public function testAnEmptyStoredValueMatchesNothing(): void
    {
        self::assertFalse($this->hasher()->verify('', ''));
        self::assertFalse($this->hasher()->verify(self::PASSWORD, ''));
    }

    public function testGarbageInTheColumnIsNotAWayIn(): void
    {
        self::assertFalse($this->hasher()->verify(self::PASSWORD, 'not a hash at all'));
    }

    private function hasher(): NativePasswordHasher
    {
        // The cheapest cost bcrypt accepts: the strength of the algorithm is not what is under
        // test here, and these tests hash a lot.
        return new NativePasswordHasher(new LegacyMd5PasswordVerifier(), PASSWORD_BCRYPT, ['cost' => 4]);
    }
}
