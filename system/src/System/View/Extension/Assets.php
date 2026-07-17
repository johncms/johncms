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

use InvalidArgumentException;
use Johncms\Http\PublicUrlResolver;
use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;
use Psr\Container\ContainerInterface;

class Assets implements ExtensionInterface
{
    /** @var array */
    private $config;

    public function __invoke(ContainerInterface $container): self
    {
        $this->config = config('johncms');

        return $this;
    }

    public static function create(ContainerInterface $container)
    {
        return (new self())($container);
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('asset', [$this, 'url']);
    }

    public function url(string $url, bool $versionStamp = false): string
    {
        $url = ltrim($url, '/');

        if ($this->isAdmin()) {
            $file = (string) realpath(PUBLIC_THEMES_PATH . 'admin/assets/' . $url);
            $resultUrl = $this->urlFromPath($file);

            if (is_file($file)) {
                return $versionStamp
                    ? $resultUrl . '?v=' . filemtime($file)
                    : $resultUrl;
            }

            throw new InvalidArgumentException('Unable to locate the asset: ' . $url);
        }

        foreach ([$this->config['skindef'], 'default'] as $skin) {
            $file = (string) realpath(PUBLIC_THEMES_PATH . $skin . '/assets/' . $url);
            $resultUrl = $this->urlFromPath($file);

            if (is_file($file)) {
                return $versionStamp
                    ? $resultUrl . '?v=' . filemtime($file)
                    : $resultUrl;
            }
        }

        throw new InvalidArgumentException('Unable to locate the asset: ' . $url);
    }

    /**
     * @param string|null $basePath Document root the URL is relative to. Defaults to PUBLIC_PATH.
     */
    public function urlFromPath(string $path, ?string $basePath = null): string
    {
        return di(PublicUrlResolver::class)->fromPath($path, $basePath);
    }

    private function isAdmin(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
        return $path === '/admin' || str_starts_with($path, '/admin/');
    }
}
