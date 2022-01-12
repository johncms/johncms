<?php

declare(strict_types=1);

namespace Johncms\Debug\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use ReflectionClass;
use Throwable;

class RouteDataCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        try {
            $route = di('route');
            $formatter = $this->getDataFormatter();

            $reflection = new ReflectionClass($route);
            $handler = $reflection->getProperty('handler');
            $handler->setAccessible(true);
            $controller = $handler->getValue($route);

            return [
                'Route name'  => $route->getName(),
                'Method'      => $route->getMethod(),
                'Path'        => $route->getPath($route->getVars()),
                'Vars'        => $formatter->formatVar($route->getVars()),
                'Controller'  => (is_array($controller) && count($controller) === 2 ? implode('::', $controller) : $formatter->formatVar($controller)),
                'Middlewares' => $formatter->formatVar($route->getMiddlewareStack()),
            ];
        } catch (Throwable) {
            return [];
        }
    }

    public function getName(): string
    {
        return 'route';
    }

    public function getWidgets(): array
    {
        return [
            'route' => [
                'icon'    => 'share',
                'widget'  => 'PhpDebugBar.Widgets.HtmlVariableListWidget',
                'map'     => 'route',
                'default' => '{}',
            ],
        ];
    }
}
