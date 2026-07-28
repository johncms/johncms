<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controller;

use Johncms\Http\Controller\ActionInvoker;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request as BaseRequest;

/**
 * Tests for ActionInvoker.
 *
 * The central guarantee: an action typed on Request receives the request travelling through
 * the middleware pipeline, never the shared instance held by the container. Without it the
 * "Request as an action argument" convention buys no request isolation,
 * and middleware that rewrites the request stops reaching controllers.
 */
final class ActionInvokerTest extends TestCase
{
    public function testWrapperTypedRequestComesFromThePipelineNotTheContainer(): void
    {
        $pipelineRequest = new Request(['from' => 'pipeline']);
        $containerRequest = new Request(['from' => 'container']);

        $result = $this->invoke(
            new ActionInvokerTestController(),
            'wrapperRequest',
            $pipelineRequest,
            [Request::class => $containerRequest],
        );

        self::assertSame($pipelineRequest, $result);
    }

    public function testBaseTypedRequestAlsoComesFromThePipeline(): void
    {
        $pipelineRequest = new Request(['from' => 'pipeline']);
        $containerRequest = new Request(['from' => 'container']);

        $result = $this->invoke(
            new ActionInvokerTestController(),
            'baseRequest',
            $pipelineRequest,
            [BaseRequest::class => $containerRequest, Request::class => $containerRequest],
        );

        self::assertSame($pipelineRequest, $result);
    }

    public function testOtherServicesAreStillResolvedFromTheContainer(): void
    {
        $service = new ActionInvokerTestService();

        $result = $this->invoke(
            new ActionInvokerTestController(),
            'service',
            new Request(),
            [ActionInvokerTestService::class => $service],
        );

        self::assertSame($service, $result);
    }

    public function testRequestAndRouteParamsAreMixedInOneSignature(): void
    {
        $request = new Request(['from' => 'pipeline']);

        $result = $this->invoke(
            new ActionInvokerTestController(),
            'mixedSignature',
            $request,
            [],
            ['id' => '42'],
        );

        self::assertSame([42, $request], $result);
    }

    public function testRouteParameterIsCastToTheDeclaredScalarType(): void
    {
        $result = $this->invoke(
            new ActionInvokerTestController(),
            'scalars',
            new Request(),
            [],
            ['id' => '7', 'ratio' => '1.5', 'flag' => '1', 'slug' => 123],
        );

        self::assertSame([7, 1.5, true, '123'], $result);
    }

    public function testDefaultValueIsUsedWhenTheRouteParameterIsMissing(): void
    {
        $result = $this->invoke(new ActionInvokerTestController(), 'withDefault', new Request());

        self::assertSame('default', $result);
    }

    public function testNullableParameterFallsBackToNull(): void
    {
        $result = $this->invoke(new ActionInvokerTestController(), 'nullable', new Request());

        self::assertNull($result);
    }

    public function testUnresolvableParameterThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to resolve parameter "id"');

        $this->invoke(new ActionInvokerTestController(), 'unresolvable', new Request());
    }

    /**
     * @param array<string, object> $services
     * @param array<string, mixed> $routeParams
     */
    private function invoke(
        object $controller,
        string $method,
        Request $request,
        array $services = [],
        array $routeParams = [],
    ): mixed {
        return (new ActionInvoker($this->container($services)))
            ->invoke($controller, $method, $request, $routeParams);
    }

    /**
     * @param array<string, object> $services
     */
    private function container(array $services): ContainerInterface
    {
        return new class ($services) implements ContainerInterface {
            /**
             * @param array<string, object> $services
             */
            public function __construct(private readonly array $services)
            {
            }

            public function get(string $id): object
            {
                if (! isset($this->services[$id])) {
                    throw new RuntimeException(sprintf('Unexpected container lookup for "%s".', $id));
                }

                return $this->services[$id];
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }
        };
    }
}
