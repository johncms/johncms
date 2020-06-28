<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\View\Extension;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;
use Psr\Container\ContainerInterface;

class Assets implements ExtensionInterface
{
    /** @var array */
    private $config;

    public function __invoke(ContainerInterface $container): self
    {
        $this->config = $container->get('config')['johncms'];

        return $this;
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('asset', [$this, 'url']);
    }

    public function url(string $url, bool $versionStamp = false): string
    {
        $url = ltrim($url, '/');

        foreach ([$this->config['skindef'], 'default'] as $skin) {
            $file = (string) realpath(THEMES_PATH . $skin . '/assets/' . $url);
            $resultUrl = $this->urlFromPath($file, ROOT_PATH);

            if (is_file($file)) {
                return $versionStamp
                    ? $resultUrl . '?v=' . filemtime($file)
                    : $resultUrl;
            }
        }

        throw new InvalidArgumentException('Unable to locate the asset: ' . $url);
    }

    public function urlFromPath(string $path, string $rootPath): string
    {
        return Str::after(realpath($path), realpath($rootPath));
    }
}
