<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Router;

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
        } catch (ReflectionException $e) {
        }
        return $parameters;
    }

    public function injectParameters(object $class, string $action_name, array $param_values): array
    {
        $parameters = $this->getMethodParameters($class, $action_name);
        $injected_parameters = [];
        foreach ($parameters as $parameter) {
            if ($parameter['class'] !== null) {
                $injected_parameters[$parameter['name']] = $this->container->get($parameter['class']);
            } elseif (array_key_exists($parameter['name'], $param_values)) {
                $injected_parameters[$parameter['name']] = $this->castValue($parameter['type'], $param_values[$parameter['name']]);
            } else {
                $injected_parameters[$parameter['name']] = $this->castValue($parameter['type'], $parameter['value']);
            }
        }
        return $injected_parameters;
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool|float|int|string
     */
    private function castValue(string $type, $value)
    {
        switch ($type) {
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            case 'bool':
                return (bool) $value;
            default:
                return $value;
        }
    }
}
