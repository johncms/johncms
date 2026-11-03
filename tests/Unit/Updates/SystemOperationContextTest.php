<?php

declare(strict_types=1);

namespace Tests\Unit\Updates;

use Johncms\Updates\SystemOperationContext;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SystemOperationContextTest extends TestCase
{
    public function testHookReportsReachTheCurrentReporterAndNestedScopesRestoreIt(): void
    {
        $context = new SystemOperationContext();
        $outer = [];
        $inner = [];
        $context->runTrusted(function () use ($context, &$inner): void {
            $context->report('before');
            $context->runTrusted(fn () => $context->report('inherited'));
            $context->runTrusted(fn () => $context->report('nested'), function (string $line) use (&$inner): void { $inner[] = $line; });
            $context->report('after');
        }, function (string $line) use (&$outer): void { $outer[] = $line; });
        self::assertSame(['before', 'inherited', 'after'], $outer);
        self::assertSame(['nested'], $inner);
        $context->runTrusted(fn () => $context->report('no reporter'));
        self::assertSame(['before', 'inherited', 'after'], $outer);
    }

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
