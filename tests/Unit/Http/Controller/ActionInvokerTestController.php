<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controller;

use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Signature fixtures for ActionInvokerTest. Every action returns what it was given so the
 * test can assert which object the invoker picked.
 */
final class ActionInvokerTestController
{
    public function wrapperRequest(Request $request): Request
    {
        return $request;
    }

    public function baseRequest(BaseRequest $request): BaseRequest
    {
        return $request;
    }

    public function service(ActionInvokerTestService $service): ActionInvokerTestService
    {
        return $service;
    }

    /**
     * @return array{0: int, 1: Request}
     */
    public function mixedSignature(int $id, Request $request): array
    {
        return [$id, $request];
    }

    /**
     * @return array{0: int, 1: float, 2: bool, 3: string}
     */
    public function scalars(int $id, float $ratio, bool $flag, string $slug): array
    {
        return [$id, $ratio, $flag, $slug];
    }

    public function withDefault(string $name = 'default'): string
    {
        return $name;
    }

    public function nullable(?string $name): ?string
    {
        return $name;
    }

    public function unresolvable(int $id): int
    {
        return $id;
    }
}
