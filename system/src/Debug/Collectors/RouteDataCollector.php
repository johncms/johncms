<?php

declare(strict_types=1);

namespace Johncms\Debug\Collectors;

use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use Throwable;

class RouteDataCollector extends DataCollector implements Renderable
{
    public function collect(): array
    {
        try {
            $route = di('route');
            $formatter = $this->getDataFormatter();

            return [
                'Route name'  => $route['_name'],
                'Vars'        => $formatter->formatVar($route),
                'Controller'  => (is_array($route['_controller']) && count($route['_controller']) === 2 ? implode('::', $route['_controller']) : $formatter->formatVar($route['_controller'])),
                'Middlewares' => $formatter->formatVar($route['_middlewares']),
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
                'widget'  => 'PhpDebugBar.Widgets.VariableListWidget',
                'map'     => 'route',
                'default' => '{}',
            ],
        ];
    }
}
