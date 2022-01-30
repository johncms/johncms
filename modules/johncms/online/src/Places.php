<?php

declare(strict_types=1);

namespace Johncms\Online;

class Places
{
    private array $places = [];
    private array $cachedPlaces = [];

    public function __construct()
    {
        $this->getPlaces();
    }

    public function __invoke(): static
    {
        return $this;
    }

    private function getPlaces()
    {
        $places = [];
        $configs = glob(MODULES_PATH . '*/*/config/places.php');
        foreach ($configs as $config) {
            $modulePLaces = require $config;
            $places = array_merge($places, $modulePLaces);
        }
        $this->places = $places;
    }

    /**
     * Get place on the site by route name
     *
     * @param string $routeName
     * @param array $routeParams
     * @return array{url: string, name: string}
     */
    public function getPlace(string $routeName, array $routeParams = []): array
    {
        $place = [
            'name' => d__('online', 'Somewhere on the site'),
            'url'  => '',
        ];

        $cacheId = $this->getCacheId($routeName, $routeParams);
        if (array_key_exists($cacheId, $this->cachedPlaces)) {
            return $this->cachedPlaces[$cacheId];
        }

        if (array_key_exists($routeName, $this->places)) {
            $placeConfig = $this->places[$routeName];
            if (is_string($placeConfig)) {
                $place['name'] = $placeConfig;
            } elseif (is_callable($placeConfig)) {
                $place['name'] = $placeConfig($routeParams);
            }
            $place['url'] = route($routeName, $routeParams);
        }

        $this->cachedPlaces[$cacheId] = $place;

        return $place;
    }

    public function getCacheId(string $routeName, array $routeParams = []): string
    {
        $cacheId = $routeName;
        if (! empty($routeParams)) {
            $cacheId .= md5(json_encode($routeParams));
        }
        return $cacheId;
    }
}
