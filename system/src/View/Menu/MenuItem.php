<?php

declare(strict_types=1);

namespace Johncms\View\Menu;

use RuntimeException;

class MenuItem
{
    protected bool $active = false;

    public function __construct(
        protected string $url = '',
        protected string $name = '',
        protected string $icon = '',
        protected string | array $counter = '',
        protected array $attributes = [],
    ) {
    }

    private function resolveCounter()
    {
        $callable = $this->counter;

        if (empty($callable)) {
            return null;
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $callable = [di($callable[0]), $callable[1]];
        }

        if (is_string($callable)) {
            $callable = di($callable[0]);
        }

        if (! is_callable($callable)) {
            throw new RuntimeException('Could not resolve a callable for this menu item counter');
        }

        return $callable();
    }

    public function toArray(): array
    {
        return [
            'url'        => $this->url,
            'name'       => $this->name,
            'icon'       => $this->icon,
            'counter'    => $this->resolveCounter(),
            'attributes' => $this->attributes,
        ];
    }
}
