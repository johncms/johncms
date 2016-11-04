<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class ConfigFactory
{
    public function __invoke(ContainerInterface $container){
        return new Config($container->get('config')['johncms']);
    }
}
