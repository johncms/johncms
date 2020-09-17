<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use HTMLPurifier_Config;
use HTMLPurifier_AttrDef_Enum;
use Psr\Container\ContainerInterface;

class HTMLPurifier
{
    public function __invoke(ContainerInterface $container): \HTMLPurifier
    {
        $htmlpurifier_config = di('config')['htmlpurifier'];
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Attr.AllowedClasses', $htmlpurifier_config['allowed_classes']);

        $def = $config->getHTMLDefinition(true);
        if ($def) {
            $def->addAttribute(
                'a',
                'target',
                new HTMLPurifier_AttrDef_Enum(
                    ['_blank', '_self', '_target', '_top']
                )
            );
            $def->addElement(
                'figure',
                'Block',
                'Flow',
                'Common',
                [ // attributes
                    'class',
                ]
            );
            $def->addElement(
                'oembed',
                'Block',
                'Flow',
                'Common',
                [ // attributes
                    'url' => 'URI',
                ]
            );
            $def->addElement(
                'figcaption',
                'Block',
                'Flow',
                'Common',
                []
            );
        }

        return new \HTMLPurifier($config);
    }
}
