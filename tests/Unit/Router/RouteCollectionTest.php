<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteCollection;
use PHPUnit\Framework\TestCase;

class RouteCollectionTest extends TestCase
{
    public function testCompile()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->get('/test', ['class', 'method'])->setName('test')->setPriority(20);
        $routeCollection->get('/test2', ['class', 'method'])->setName('test2')->setPriority(100);
        $compiledCollection = $routeCollection->compile()->all();

        self::assertEquals('test2', array_key_first($compiledCollection));
        self::assertEquals('test', array_key_last($compiledCollection));
    }

    public function testGroup()
    {
        $routeCollection = new RouteCollection();
        $routeCollection->group('path', function (RouteCollection $collection) {
            $collection->get('/test', ['class', 'method'])->setName('test')->setPriority(20);
            $collection->get('/test2', ['class', 'method'])->setName('test2')->setPriority(100);
        })->setNamePrefix('namePrefix.');
        $compiledCollection = $routeCollection->compile()->all();

        self::assertArrayHasKey('namePrefix.test', $compiledCollection);
        self::assertArrayHasKey('namePrefix.test2', $compiledCollection);

        self::assertEquals('/path/test2', $compiledCollection['namePrefix.test2']->getPath());
        self::assertEquals('/path/test', $compiledCollection['namePrefix.test']->getPath());
    }
}
