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

use Johncms\Modules\Manifest\ModuleManifest;

/**
 * Every place a module can come from, behind one answer.
 *
 * Two of them: modules/, where the CMS ships its own and where an archive is unpacked, and
 * vendor/, where Composer keeps what it installed. Nothing else in the system needs to know which
 * of the two a module came from — the manifest carries its path, and everything works from that.
 *
 * The order matters only when the same key turns up twice, which means the site has the module
 * both ways. The one in modules/ wins: it is the one a person put there.
 */
final readonly class ChainModuleRepository implements ModuleRepositoryInterface
{
    /**
     * @param list<ModuleRepositoryInterface> $repositories In order of precedence.
     */
    public function __construct(private array $repositories)
    {
    }

    public function all(): array
    {
        $modules = [];
        foreach ($this->repositories as $repository) {
            foreach ($repository->all() as $key => $manifest) {
                $modules[$key] ??= $manifest;
            }
        }

        ksort($modules);

        return $modules;
    }

    public function find(string $key): ?ModuleManifest
    {
        return $this->all()[$key] ?? null;
    }

    public function forget(): void
    {
        foreach ($this->repositories as $repository) {
            $repository->forget();
        }
    }
}
