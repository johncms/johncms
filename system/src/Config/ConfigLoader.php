<?php

declare(strict_types=1);

namespace Johncms\Config;

final readonly class ConfigLoader
{
    public function __construct(
        private string $configPath,
    ) {
    }

    public function load(): array
    {
        $config = [];

        foreach ($this->getConfigFiles() as $file) {
            /**
             * @psalm-suppress UnresolvableInclude
             * @var array $data
             */
            $data = require $file;

            $config = array_replace_recursive($config, $data);
        }

        return $config;
    }

    /**
     * @return list<string>
     */
    private function getConfigFiles(): array
    {
        $global = glob($this->configPath . DS . '*.global.php') ?: [];
        $local = glob($this->configPath . DS . '*.local.php') ?: [];

        sort($global);
        sort($local);

        return [...$global, ...$local];
    }
}
