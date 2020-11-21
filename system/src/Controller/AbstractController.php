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
    public function runAction(string $action_name)
    {
        if (! method_exists($this, $action_name)) {
            throw new BadMethodCallException(sprintf('Method %s::%s does not exist.', static::class, $action_name));
        }
        $dependencies = $this->injectDependencies($action_name);
        return $this->$action_name(...array_values($dependencies));
    }

    private function getMethodDependencies(string $action_name): array
    {
        $parameters = [];
        try {
            $reflection_method = new ReflectionMethod(static::class, $action_name);
            foreach ($reflection_method->getParameters() as $parameter) {
                if ($parameter->getClass() === null) {
                    continue;
                }
                $parameters[] = [
                    'name'  => $parameter->getName(),
                    'class' => $parameter->getClass()->getName(),
                ];
            }
        } catch (ReflectionException $e) {
        }
        return $parameters;
    }

    private function injectDependencies(string $action_name): array
    {
        $dependencies = $this->getMethodDependencies($action_name);
        $parameters = [];
        foreach ($dependencies as $dependency) {
            $parameters[$dependency['name']] = di($dependency['class']);
        }
        return $parameters;
    }
}
