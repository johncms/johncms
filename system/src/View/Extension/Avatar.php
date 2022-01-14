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

use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;
use Psr\Container\ContainerInterface;
use Throwable;

class Avatar implements ExtensionInterface
{
    private ?Engine $engine = null;

    public function __invoke(ContainerInterface $container): self
    {
        return $this;
    }

    public function register(Engine $engine): void
    {
        $this->engine = $engine;
        $engine->registerFunction('avatar', [$this, 'getUserAvatar']);
    }

    /**
     * @throws Throwable
     */
    public function getUserAvatar(?string $avatarUrl = '', ?string $userName = ''): string
    {
        $firstSymbols = ! empty($userName) ? $this->getFirstSymbols($userName) : '';
        return $this->engine->render('system::app/avatar', [
            'avatar_url'    => $avatarUrl,
            'username'      => $userName,
            'first_symbols' => $firstSymbols,
            'color'         => $this->getColor($userName),
        ]);
    }

    private function getFirstSymbols(string $username): string
    {
        $return = '';
        $name = explode(' ', $username);
        $return .= mb_substr($name[0], 0, 1);
        if (! empty($name[1])) {
            $return .= mb_substr($name[1], 0, 1);
        }
        return $return;
    }

    private function getColor(?string $userName = ''): string
    {
        $colors = config('johncms.avatar_colors');
        return $colors[(mb_strlen((string) $userName) % count($colors))] ?? $colors[0];
    }
}
