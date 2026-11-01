<?php

declare(strict_types=1);

namespace Tests\Unit\Updates;

use Johncms\Updates\SystemOperationContext;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SystemOperationContextTest extends TestCase
{
    public function testFailureClosesTheTrustedScope(): void
    {
        $context = new SystemOperationContext();
        try {
            $context->runTrusted(function () use ($context): void {
                $context->requireActive();
                throw new RuntimeException('Failed hook');
            });
        } catch (RuntimeException $exception) {
            self::assertSame('Failed hook', $exception->getMessage());
        }
        $this->expectException(LogicException::class);
        $context->requireActive();
    }
}
