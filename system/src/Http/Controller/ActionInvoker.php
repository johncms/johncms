<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Johncms\Http\Request;
use Psr\Container\ContainerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

final readonly class ActionInvoker
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @param array<string, mixed> $routeParams
     */
    public function invoke(object $controller, string $method, Request $request, array $routeParams = []): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);

        $arguments = $this->resolveArguments($reflection, $request, $routeParams);

        return $controller->{$method}(...$arguments);
    }

    /**
     * @param array<string, mixed> $routeParams
     * @return list<mixed>
     */
    private function resolveArguments(
        ReflectionMethod $reflection,
        Request $request,
        array $routeParams
    ): array {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // Class-based DI
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();

                // An action argument gets the request travelling through the pipeline, not the
                // container's shared instance: that one goes stale under a long-running runtime,
                // and a middleware handing the next stage a different request object would
                // otherwise never reach the action. instanceof covers both the Johncms wrapper
                // and a base HttpFoundation type hint.
                if ($request instanceof $typeName) {
                    $arguments[] = $request;
                    continue;
                }

                $arguments[] = $this->container->get($typeName);
                continue;
            }

            // Route parameter by name. Only a named type tells us what to cast to; a union or
            // intersection type has no single name, so the raw value is passed through.
            if (array_key_exists($name, $routeParams)) {
                $arguments[] = $this->castValue(
                    $type instanceof ReflectionNamedType ? $type->getName() : null,
                    $routeParams[$name],
                );
                continue;
            }

            // Default value
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            // Nullable fallback
            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }

            throw new RuntimeException(
                sprintf(
                    'Unable to resolve parameter "%s" for %s::%s',
                    $name,
                    $reflection->getDeclaringClass()->getName(),
                    $reflection->getName(),
                )
            );
        }

        return $arguments;
    }

    private function castValue(?string $type, mixed $value): mixed
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $value,
        };
    }
}
