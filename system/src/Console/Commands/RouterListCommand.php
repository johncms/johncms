<?php

declare(strict_types=1);

namespace Johncms\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

#[AsCommand(
    name: 'router:list',
    description: 'List registered routes',
)]
final class RouterListCommand extends Command
{
    public function __construct(
        private readonly RouteCollection $routes,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('details', null, InputOption::VALUE_NONE, 'Show handler, middleware and requirements');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $showDetails = (bool) $input->getOption('details');

        $rows = $this->buildRows($showDetails);

        if ($rows === []) {
            $io->writeln('<comment>No routes were found.</comment>');
            return self::SUCCESS;
        }

        $headers = ['Name', 'Methods', 'Path'];
        if ($showDetails) {
            $headers = [...$headers, 'Handler', 'Middlewares', 'Requirements'];
        }

        $io->table($headers, array_column($rows, 'columns'));

        return self::SUCCESS;
    }

    /**
     * @return list<array{sort_path: string, sort_methods: string, sort_name: string, columns: list<string>}>
     */
    private function buildRows(bool $showDetails): array
    {
        $rows = [];

        foreach ($this->routes->all() as $name => $route) {
            $methods = $route->getMethods();
            $methodsLabel = $methods === [] ? 'ANY' : implode('|', $methods);

            $columns = [(string) $name, $methodsLabel, $route->getPath()];

            if ($showDetails) {
                $columns[] = $this->formatHandler($route);
                $columns[] = $this->formatMiddlewares($route);
                $columns[] = $this->formatRequirements($route);
            }

            $rows[] = [
                'sort_path' => $route->getPath(),
                'sort_methods' => $methodsLabel,
                'sort_name' => (string) $name,
                'columns' => $columns,
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            $pathCompare = $left['sort_path'] <=> $right['sort_path'];
            if ($pathCompare !== 0) {
                return $pathCompare;
            }

            $methodCompare = $left['sort_methods'] <=> $right['sort_methods'];
            if ($methodCompare !== 0) {
                return $methodCompare;
            }

            return $left['sort_name'] <=> $right['sort_name'];
        });

        return $rows;
    }

    private function formatHandler(Route $route): string
    {
        $handler = $route->getDefault('_handler');

        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler)) {
            if (
                isset($handler[0], $handler[1])
                && is_string($handler[0])
                && is_string($handler[1])
            ) {
                return $handler[0] . '::' . $handler[1];
            }

            $encoded = json_encode($handler, JSON_UNESCAPED_SLASHES);
            return $encoded !== false ? $encoded : 'array';
        }

        if (is_object($handler)) {
            return $handler::class;
        }

        if ($handler === null) {
            return '-';
        }

        return (string) $handler;
    }

    private function formatMiddlewares(Route $route): string
    {
        $middlewares = $route->getDefault('_middlewares');
        if (! is_array($middlewares) || $middlewares === []) {
            return '-';
        }

        $result = [];
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $result[] = $middleware;
                continue;
            }

            if (is_array($middleware) && isset($middleware[0], $middleware[1]) && is_string($middleware[0]) && is_string($middleware[1])) {
                $result[] = $middleware[0] . '::' . $middleware[1];
                continue;
            }

            if (is_object($middleware)) {
                $result[] = $middleware::class;
                continue;
            }

            $result[] = get_debug_type($middleware);
        }

        return implode(', ', $result);
    }

    private function formatRequirements(Route $route): string
    {
        $requirements = $route->getRequirements();
        if ($requirements === []) {
            return '-';
        }

        $result = [];
        foreach ($requirements as $key => $value) {
            $result[] = $key . '=' . $value;
        }

        return implode(', ', $result);
    }
}
