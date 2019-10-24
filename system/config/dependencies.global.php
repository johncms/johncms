<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

return [
    'dependencies' => [
        'factories' => [
            Johncms\Api\BbcodeInterface::class      => Johncms\Utility\Bbcode::class,
            Johncms\Api\ConfigInterface::class      => Johncms\ConfigFactory::class,
            Johncms\Api\EnvironmentInterface::class => Johncms\Environment::class,
            Johncms\Api\ToolsInterface::class       => Johncms\Utility\Tools::class,
            Johncms\Api\UserInterface::class        => Johncms\UserFactory::class,
            PDO::class                              => Johncms\Database\PdoFactory::class,

            'counters' => Johncms\Utility\Counters::class,
        ],

        // DEPRECATED!!!
        // Данные псевдонимы запрещены к использованию и будут удалены в ближайших версиях.
        // В своих разработках используйте вызов соответствующих интерфейсов
        'aliases' => [
            Johncms\User::class => Johncms\Api\UserInterface::class,
            'bbcode'            => Johncms\Api\BbcodeInterface::class,
            'env'               => Johncms\Api\EnvironmentInterface::class,
            'tools'             => Johncms\Api\ToolsInterface::class,
        ],
    ],
];
