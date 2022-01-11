<?php

declare(strict_types=1);

namespace Johncms\Debug;

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DebugBarException;
use DebugBar\StandardDebugBar;
use Psr\Container\ContainerInterface;

class DebugBar
{
    /**
     * @throws DebugBarException
     */
    public function __invoke(ContainerInterface $container): StandardDebugBar
    {
        $debugBar = new StandardDebugBar();
        $debugBar->getJavascriptRenderer('/themes/default/assets/debugbar');
        $debugBar->addCollector(new ConfigCollector(config()));
        return $debugBar;
    }
}
