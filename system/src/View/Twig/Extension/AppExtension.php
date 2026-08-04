<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\AppVariable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

/**
 * Publishes the app global. It is built when the first template of a request is rendered, not
 * when the environment is assembled, so a request that renders nothing pays nothing.
 */
final class AppExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var callable(): AppVariable */
    private $app;

    /**
     * @param callable(): AppVariable $app
     */
    public function __construct(callable $app)
    {
        $this->app = $app;
    }

    public function getGlobals(): array
    {
        return ['app' => ($this->app)()];
    }

    /**
     * A single configuration value by its dotted key. The configuration array as a whole is not
     * published: a template asks for what it needs by name.
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('config', config(...)),
        ];
    }
}
