<?php

namespace PHPSTORM_META {

    override(
        \di(0),
        map(
            [
                ''         => '@',
                'config'   => 'array',
                'counters' => \Johncms\Counters::class,
            ]
        )
    );
    override(
        \Psr\Container\ContainerInterface::get(0),
        map(
            [
                ''         => '@',
                'config'   => 'array',
                'counters' => \Johncms\Counters::class,
            ]
        )
    );
}
