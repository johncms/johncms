<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class ToolsFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
