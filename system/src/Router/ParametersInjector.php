<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Router;

use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionMethod;

class ParametersInjector
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param object $class
     * @param string $action_name
     * @return array
     * @psalm-suppress UndefinedMethod
     */
    private function getMethodParameters(object $class, string $action_name): array
    {
        $parameters = [];
        try {
            $reflection_method = new ReflectionMethod($class, $action_name);
            foreach ($reflection_method->getParameters() as $parameter) {
                $type = $parameter->getType();
                $class_name = $type && ! $type->isBuiltin() ? $type->getName() : null;
                $parameters[] = [
                    'name'  => $parameter->getName(),
                    'class' => $class_name ?? null,
                    'value' => $parameter->isOptional() ? $parameter->getDefaultValue() : null,
                    'type'  => $type !== null ? $type->getName() : '',
                ];
            }
        } catch (ReflectionException) {
        }
        return $parameters;
    }

    public function injectParameters(object $class, string $actionName, array $paramValues): array
    {
        $parameters = $this->getMethodParameters($class, $actionName);
        $injectedParameters = [];
        foreach ($parameters as $parameter) {
            if ($parameter['class'] !== null) {
                $injectedParameters[$parameter['name']] = $this->container->get($parameter['class']);
            } elseif (array_key_exists($parameter['name'], $paramValues)) {
                $injectedParameters[$parameter['name']] = $this->castValue($parameter['type'], $paramValues[$parameter['name']]);
            } else {
                $injectedParameters[$parameter['name']] = $this->castValue($parameter['type'], $parameter['value']);
            }
        }
        return $injectedParameters;
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool|float|int|string
     */
    private function castValue(string $type, mixed $value): float|bool|int|string
    {
        return match ($type) {
            'int' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool' => (bool) $value,
            default => $value,
        };
    }
}
