<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules;

/**
 * What an operation on a module actually did, step by step.
 *
 * Installing is half a dozen things in a row — migrations, hooks, permissions, caches — and any of
 * them can be the one that failed. A boolean would leave the person guessing; this is what the
 * console prints and what the admin panel will show.
 */
final class ModuleOperationResult
{
    /** @var list<array{step: string, outcome: string, detail: string|null}> */
    private array $steps = [];

    private bool $failed = false;

    public function done(string $step, ?string $detail = null): self
    {
        $this->steps[] = ['step' => $step, 'outcome' => 'done', 'detail' => $detail];

        return $this;
    }

    public function skipped(string $step, ?string $detail = null): self
    {
        $this->steps[] = ['step' => $step, 'outcome' => 'skipped', 'detail' => $detail];

        return $this;
    }

    public function failed(string $step, string $reason): self
    {
        $this->steps[] = ['step' => $step, 'outcome' => 'failed', 'detail' => $reason];
        $this->failed = true;

        return $this;
    }

    public function isSuccessful(): bool
    {
        return ! $this->failed;
    }

    /**
     * @return list<array{step: string, outcome: string, detail: string|null}>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    /**
     * The reason it failed, for a caller that only needs the sentence.
     */
    public function error(): ?string
    {
        foreach ($this->steps as $step) {
            if ($step['outcome'] === 'failed') {
                return $step['detail'];
            }
        }

        return null;
    }
}
