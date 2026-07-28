<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Album\Exceptions;

use Johncms\Exceptions\ValidationException;
use Johncms\Modules\Album\Application\Exceptions\AlbumValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for a fatal error found by PHPStan.
 *
 * The class used to redeclare the inherited $errors property as promoted readonly, which PHP
 * rejects at class-declaration time — so merely constructing the exception killed the request.
 * Every invalid album form submission hit it.
 */
final class AlbumValidationExceptionTest extends TestCase
{
    public function testCanBeConstructedAndExposesItsErrors(): void
    {
        $exception = new AlbumValidationException(['The album already exists', 'Wrong data']);

        self::assertSame(['The album already exists', 'Wrong data'], $exception->getErrors());
    }

    public function testIsCaughtAsAValidationException(): void
    {
        $exception = new AlbumValidationException([]);

        self::assertInstanceOf(ValidationException::class, $exception);
        self::assertSame('Validation error', $exception->getMessage());
    }
}
