<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Controller;

use BadMethodCallException;
use Illuminate\Container\Container;
use Johncms\System\Container\Factory;
use ReflectionException;
use ReflectionMethod;

abstract class AbstractController
{
    public function runAction(string $action_name, array $param_values = [])
    {
        if (! method_exists($this, $action_name)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $action_name));
        }
        $parameters = $this->injectParameters($action_name, $param_values);
        return $this->$action_name(...array_values($parameters));
    }

    private function getMethodParameters(string $action_name): array
    {
        $parameters = [];
        try {
            $reflection_method = new ReflectionMethod(static::class, $action_name);
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

    private function injectParameters(string $action_name, array $param_values): array
    {
        $parameters = $this->getMethodParameters($action_name);
        $injected_parameters = [];
        foreach ($parameters as $parameter) {
            if ($parameter['class'] !== null) {
                // TODO: Replace container
                $container = Factory::getContainer();
                if ($container->has($parameter['class'])) {
                    $injected_parameters[$parameter['name']] = $container->get($parameter['class']);
                } else {
                    $injector = Container::getInstance();
                    $injected_parameters[$parameter['name']] = $injector->get($parameter['class']);
                }
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
     * @param $value
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
