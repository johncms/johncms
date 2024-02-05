<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteRequirements;
use PHPUnit\Framework\TestCase;

class RouteRequirementsTest extends TestCase
{
    public function testReplaceTemplates()
    {
        $requirements = new RouteRequirements();
        $pathTemplates = [
            '/forum/{name:word}'                                             => '/forum/{name<[a-zA-Z]+>}',
            '/forum/{topic:slug}'                                            => '/forum/{topic<[\w.+-]+>}',
            '/forum/{page:number?}'                                          => '/forum/{page<\d+>?}',
            '/{slug:path}'                                                   => '/{slug<[\w/+-]+>}',
            '/forum/{name:word}/{subName:word}/{topic:slug?}/{page:number?}' => '/forum/{name<[a-zA-Z]+>}/{subName<[a-zA-Z]+>}/{topic<[\w.+-]+>?}/{page<\d+>?}',
        ];

        foreach ($pathTemplates as $template => $expected) {
            self::assertEquals($expected, $requirements->replaceTemplates($template));
        }
    }
}
