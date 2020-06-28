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

use Mobicms\Render\Engine;
use Mobicms\Render\ExtensionInterface;
use Psr\Container\ContainerInterface;

class Avatar implements ExtensionInterface
{
    /** @var Assets */
    private $assets;

    public function __invoke(ContainerInterface $container): self
    {
        $this->assets = $container->get(Assets::class);
        return $this;
    }

    public function register(Engine $engine): void
    {
        $engine->registerFunction('avatar', [$this, 'getUserAvatar']);
    }

    public function getUserAvatar(int $userId): string
    {
        if ($userId > 0) {
            $avatar = UPLOAD_PATH . 'users/avatar/' . $userId . '.png';
            if (file_exists($avatar)) {
                return $this->assets->urlFromPath($avatar, ROOT_PATH) .
                    '?v=' . filemtime($avatar);
            }
        }

        return $this->assets->url('icons/user.svg');
    }
}
