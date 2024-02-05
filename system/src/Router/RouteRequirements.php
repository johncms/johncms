<?php

declare(strict_types=1);

namespace Johncms\Router;

class RouteRequirements
{
    private array $presets = [];

    public function __construct()
    {
        $this->setDefaultPresets();
    }

    private function setDefaultPresets(): void
    {
        $this->presets += [
            'number' => '\d+',
            'word'   => '[a-zA-Z]+',
            'slug'   => '[\w.+-]+',
            'path'   => '[\w/+-]+',
        ];
    }

    public function replaceTemplates(string $path): string
    {
        preg_match_all('/\{([\w\:]+?)\??\}/', $path, $matches);

        foreach ($matches[0] as $match) {
            $preset = explode(':', trim($match, '{}?'));
            if (! isset($preset[1]) || ! array_key_exists($preset[1], $this->presets)) {
                continue;
            }

            if (str_contains($match, '?')) {
                $path = str_replace($match, '{' . $preset[0] . '<' . $this->presets[$preset[1]] . '>?}', $path);
            } else {
                $path = str_replace($match, '{' . $preset[0] . '<' . $this->presets[$preset[1]] . '>}', $path);
            }
        }

        return $path;
    }
}
