<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Repository;

use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;

/**
 * Reading and saving the captcha settings (the `captcha` key of captcha.local.php).
 */
interface CaptchaConfigRepositoryInterface
{
    /**
     * @return array<string, mixed>
     */
    public function get(): array;

    /**
     * @param array<string, mixed> $captcha
     * @throws ConfigWriteException
     */
    public function save(array $captcha): void;
}
