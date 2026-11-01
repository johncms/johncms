<?php

declare(strict_types=1);

namespace Johncms\Updates;

use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Modules\ModuleRegistry;
use RuntimeException;

/** Durable completion markers; cache clearing must not remove them. */
final readonly class SystemUpdateState implements SystemUpdateStateInterface
{
    public function __construct(
        private PendingMigrations $migrations,
        private ModuleRegistry $modules,
        private string $path = DATA_PATH . 'system-update.json',
    ) {
    }

    public function isRequired(): bool
    {
        $state = $this->read();
        if (($state['cms'] ?? null) !== CMS_VERSION || ($state['running'] ?? false)) {
            return true;
        }
        foreach ($this->modules->enabled() as $key => $module) {
            if (($state['modules'][$key] ?? null) !== ($module->version ?? CMS_VERSION)) {
                return true;
            }
        }
        return $this->migrations->count() > 0;
    }

    public function begin(?string $module = null): void
    {
        $state = $this->read();
        if ($module === null) {
            $state['running'] = true;
        } else {
            unset($state['modules'][$module]);
        }
        $this->write($state);
    }

    public function complete(?string $module = null): void
    {
        $state = $this->read();
        if ($module === null) {
            $state = ['cms' => CMS_VERSION, 'running' => false, 'modules' => []];
        }
        foreach ($this->modules->enabled() as $key => $manifest) {
            if ($module === null || $module === $key) {
                $state['modules'][$key] = $manifest->version ?? CMS_VERSION;
            }
        }
        $this->write($state);
    }

    /** @return array{cms?: string, running?: bool, modules?: array<string, string>} */
    private function read(): array
    {
        if (! is_file($this->path)) {
            return [];
        }
        $raw = file_get_contents($this->path);
        if ($raw === false) {
            throw new RuntimeException('Cannot read system update state: ' . $this->path);
        }
        $state = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        if (
            ! is_array($state) || (isset($state['cms']) && ! is_string($state['cms']))
            || (isset($state['running']) && ! is_bool($state['running']))
            || (isset($state['modules']) && ! is_array($state['modules']))
        ) {
            throw new RuntimeException('Invalid system update state: ' . $this->path);
        }
        foreach ($state['modules'] ?? [] as $version) {
            if (! is_string($version)) {
                throw new RuntimeException('Invalid module version in system update state.');
            }
        }
        return $state;
    }

    /** @param array{cms?: string, running?: bool, modules?: array<string, string>} $state */
    private function write(array $state): void
    {
        $temporary = tempnam(dirname($this->path), '.system-update-');
        if ($temporary === false) {
            throw new RuntimeException('Cannot create system update state.');
        }
        try {
            if (
                ! chmod($temporary, 0o644)
                || file_put_contents($temporary, json_encode($state, JSON_THROW_ON_ERROR)) === false
                || ! rename($temporary, $this->path)
            ) {
                throw new RuntimeException('Cannot save system update state: ' . $this->path);
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
