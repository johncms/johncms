<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controller;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * A controller is a shared service, so its constructor runs once per process while the page it
 * builds is answered many times. Anything the constructor asks a dependency to do — add a
 * breadcrumb, register a translation domain, read the current request — therefore happens for the
 * first request of the process and stays in place for every request after it. Under FPM the
 * process ends with the request and the mistake is invisible; in a worker runtime it is a visitor
 * getting the breadcrumbs, the language or the page title of whoever came before them.
 *
 * So constructors declare dependencies and nothing else: the work belongs to the action. What a
 * page needs because of where it lives is set up by the pipeline instead — ModuleContext for the
 * module of the route, AdminAreaContext for the panel.
 *
 * Assigning a plain value (`$this->config = config('news')`) is allowed: it is process state, the
 * same for every request.
 */
final class ControllerConstructorTest extends TestCase
{
    public function testNoControllerCallsItsDependenciesFromTheConstructor(): void
    {
        $offenders = [];

        foreach ($this->controllers() as $file) {
            $body = $this->constructorBody((string) file_get_contents($file));

            if ($body === null) {
                continue;
            }

            if (preg_match('/\$this->[a-zA-Z_]+->/', $body) === 1) {
                $offenders[] = str_replace(ROOT_PATH, '', $file);
            }
        }

        self::assertSame(
            [],
            $offenders,
            'A controller constructor must not do the work of a request. Move it into the action: '
            . implode(', ', $offenders)
        );
    }

    /**
     * The body between the brace opening the constructor and the one closing it, or null when the
     * class declares no constructor.
     */
    private function constructorBody(string $code): ?string
    {
        $signature = strpos($code, 'public function __construct(');

        if ($signature === false) {
            return null;
        }

        $open = strpos($code, ') {', $signature);

        if ($open === false) {
            return null;
        }

        $depth = 1;
        $body = '';

        for ($i = $open + 3; $depth > 0 && $i < strlen($code); $i++) {
            $depth += match ($code[$i]) {
                '{' => 1,
                '}' => -1,
                default => 0,
            };

            if ($depth > 0) {
                $body .= $code[$i];
            }
        }

        return $body;
    }

    /**
     * @return list<string>
     */
    private function controllers(): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(MODULES_PATH, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), 'Controller.php')) {
                $files[] = $file->getPathname();
            }
        }

        self::assertNotSame([], $files, 'No controllers were found, so the test proves nothing.');

        return $files;
    }
}
