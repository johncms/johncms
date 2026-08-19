<?php

declare(strict_types=1);

namespace Tests\Support;

use Johncms\Modules\Admin\Domain\Repository\CaptchaConfigRepositoryInterface;

/**
 * Keeps what would have been written to captcha.local.php, so a test can look at it instead of
 * at the filesystem.
 */
final class RecordingCaptchaConfigRepository implements CaptchaConfigRepositoryInterface
{
    /** @var array<string, mixed> */
    public array $saved = [];

    public function get(): array
    {
        return (array) config('captcha', []);
    }

    public function save(array $captcha): void
    {
        $this->saved = $captcha;
    }
}
