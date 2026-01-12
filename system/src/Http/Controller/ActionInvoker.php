<?php

declare(strict_types=1);

namespace Johncms\Http\Controller;

use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use RuntimeException;

final readonly class ActionInvoker
{
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    public function invoke(object $controller, string $method, array $routeParams = []): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);

        $arguments = $this->resolveArguments($reflection, $routeParams);

        return $controller->{$method}(...$arguments);
    }

    private function resolveArguments(ReflectionFunctionAbstract $reflection, array $routeParams): array
    {
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            // Class-based DI
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $arguments[] = $this->container->get($type->getName());
                continue;
            }

            // Route parameter by name
            if (array_key_exists($name, $routeParams)) {
                $arguments[] = $this->castValue(
                    $type?->getName(),
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
                    $reflection->getDeclaringClass()?->getName(),
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
