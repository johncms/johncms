<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RouteRequirements;
use PHPUnit\Framework\TestCase;

final class RouteRequirementsTest extends TestCase
{
    public function testReplaceTemplatesUsesDefaultPresets(): void
    {
        $requirements = new RouteRequirements();

        self::assertSame('/items/{id<\d+>}', $requirements->replaceTemplates('/items/{id:number}'));
        self::assertSame('/user/{name<[a-zA-Z]+>}', $requirements->replaceTemplates('/user/{name:word}'));
        self::assertSame('/post/{slug<[\w.+-]+>}', $requirements->replaceTemplates('/post/{slug:slug}'));
        self::assertSame('/files/{path<[\w/+-]+>}', $requirements->replaceTemplates('/files/{path:path}'));
    }

    public function testReplaceTemplatesSupportsOptionalParameters(): void
    {
        $requirements = new RouteRequirements();

        self::assertSame('/topic/{id<\d+>?}', $requirements->replaceTemplates('/topic/{id:number?}'));
    }

    public function testReplaceTemplatesSkipsUnknownPreset(): void
    {
        $requirements = new RouteRequirements();

        self::assertSame('/topic/{id:unknown}', $requirements->replaceTemplates('/topic/{id:unknown}'));
    }
}
