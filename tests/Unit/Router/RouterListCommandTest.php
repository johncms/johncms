<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Console\Commands\RouterListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

final class RouterListCommandTest extends TestCase
{
    public function testExecuteShowsMessageWhenNoRoutesFound(): void
    {
        $tester = new CommandTester(new RouterListCommand(new RouteCollection()));

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('No routes were found.', $tester->getDisplay());
    }

    public function testExecuteRendersBaseTableAndSortsRows(): void
    {
        $routes = new RouteCollection();
        $routes->add('z.route', new Route('/z-path', ['_handler' => 'z_handler'], methods: ['POST']));
        $routes->add('a.route', new Route('/a-path', ['_handler' => 'a_handler'], methods: ['GET']));

        $tester = new CommandTester(new RouterListCommand($routes));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Name', $display);
        self::assertStringContainsString('Methods', $display);
        self::assertStringContainsString('Path', $display);
        self::assertStringNotContainsString('Handler', $display);
        self::assertTrue(
            strpos($display, '/a-path') < strpos($display, '/z-path'),
            'Routes should be sorted by path.'
        );
    }

    public function testExecuteRendersDetailsColumns(): void
    {
        $routes = new RouteCollection();
        $routes->add(
            'forum.topic',
            new Route(
                '/forum/topic/{id}',
                [
                    '_handler' => ['Forum\\TopicController', 'show'],
                    '_middlewares' => ['auth', ['Forum\\TopicAccessMiddleware', 'check']],
                ],
                ['id' => '\\d+'],
                methods: ['GET', 'POST'],
            )
        );

        $tester = new CommandTester(new RouterListCommand($routes));

        $exitCode = $tester->execute(['--details' => true]);
        $display = $tester->getDisplay();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Handler', $display);
        self::assertStringContainsString('Middlewares', $display);
        self::assertStringContainsString('Requirements', $display);
        self::assertStringContainsString('Forum\\TopicController::show', $display);
        self::assertStringContainsString('auth, Forum\\TopicAccessMiddleware::check', $display);
        self::assertStringContainsString('id=\\d+', $display);
    }
}
