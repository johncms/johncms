<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Container;

use Johncms\ConfigProvider as JohncmsConfigProvider;
use Laminas\ConfigAggregator\{
    ArrayProvider,
    ConfigAggregator,
    PhpFileProvider
};

class Config
{
    /** @var string */
    private $cacheFile = 'system-config.cache';

    public function __invoke(): array
    {
        $aggregator = new ConfigAggregator(
            [
                // Include cache configuration
                new ArrayProvider(['config_cache_path' => $this->cacheFile]),

                // Include packages configuration
                JohncmsConfigProvider::class,

                // Load application config in a pre-defined order
                new PhpFileProvider(CONFIG_PATH . 'autoload/{{,*.}global,{,*.}local}.php'),
            ],
            $this->cacheFile
        );

        return $aggregator->getMergedConfig();
    }
}
