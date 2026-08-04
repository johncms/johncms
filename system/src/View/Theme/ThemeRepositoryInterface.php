<?php

declare(strict_types=1);

namespace Johncms\View\Theme;

interface ThemeRepositoryInterface
{
    public function find(string $name): ?ThemeDTO;

    public function exists(string $name): bool;

    /**
     * Every installed theme, keyed by name.
     *
     * @return array<string, ThemeDTO>
     */
    public function all(): array;
}
