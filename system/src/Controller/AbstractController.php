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
                $class = $parameter->getClass();
                $parameters[] = [
                    'name'  => $parameter->getName(),
                    'class' => $class !== null ? $class->getName() : null,
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
                $injected_parameters[$parameter['name']] = di($parameter['class']);
            } elseif (array_key_exists($parameter['name'], $param_values)) {
                $injected_parameters[$parameter['name']] = $param_values[$parameter['name']];
            }
        }
        return $injected_parameters;
    }
}
