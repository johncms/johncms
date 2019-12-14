<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\View\Extension;

use Johncms\System\Config\Config;
use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;
use Psr\Container\ContainerInterface;

class Assets implements ExtensionInterface
{
    /** @var Config */
    private $config;

    public function __invoke(ContainerInterface $container) : self
    {
        $this->config = $container->get(Config::class);

        return $this;
    }

    public function register(Engine $engine) : void
    {
        $engine->registerFunction('asset', [$this, 'url']);
    }

    public function url(string $url, bool $versionStamp = false) : string
    {
        $url = ltrim($url, '/');

        foreach ([$this->config->skindef, 'default'] as $skin) {
            $file = (string) realpath(ROOT_PATH . 'themes/' . $skin . '/assets/' . $url);
            $resultUrl = $this->config->homeurl . '/themes/' . $skin . '/assets/' . $url;

            if (is_file($file)) {
                return $versionStamp
                    ? $resultUrl . '?v=' . filemtime($file)
                    : $resultUrl;
            }
        }

        throw new \InvalidArgumentException('Unable to locate the asset: ' . $resultUrl);
    }
}
