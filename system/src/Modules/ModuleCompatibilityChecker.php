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

use Composer\Semver\Semver;
use Johncms\Modules\Manifest\ModuleManifest;
use Throwable;

/**
 * Answers one question: may this module be loaded here?
 *
 * Three things can say no — the version of PHP, the version of the CMS, and a module it is built
 * against that is not loaded. The answer is a sentence, not a boolean, because every one of these
 * ends up in front of a person who has to decide what to do about it.
 *
 * Constraints are read by composer/semver, the same library that reads them in composer.json.
 */
final readonly class ModuleCompatibilityChecker
{
    public function __construct(
        private string $phpVersion = PHP_VERSION,
        private string $cmsVersion = CMS_VERSION,
    ) {
    }

    /**
     * @param array<string, string> $loadedModules Key to version, of the modules already loaded.
     * @return string|null The reason it may not be loaded, or null when it may.
     */
    public function check(ModuleManifest $manifest, array $loadedModules = []): ?string
    {
        $requires = $manifest->requires;

        if ($requires->php !== null && ! $this->satisfies($this->phpVersion, $requires->php)) {
            return sprintf('Requires PHP %s, and this site runs %s.', $requires->php, $this->phpVersion);
        }

        if ($requires->johncms !== null && ! $this->satisfies($this->cmsVersion, $requires->johncms)) {
            return sprintf('Requires JohnCMS %s, and this site is %s.', $requires->johncms, $this->cmsVersion);
        }

        foreach ($requires->modules as $key => $constraint) {
            if (! array_key_exists($key, $loadedModules)) {
                return sprintf('Requires the module "%s", which is not installed or is switched off.', $key);
            }

            if (! $this->satisfies($loadedModules[$key], $constraint)) {
                return sprintf(
                    'Requires "%s" %s, and this site has %s.',
                    $key,
                    $constraint,
                    $loadedModules[$key]
                );
            }
        }

        return null;
    }

    /**
     * A version this cannot parse — a distribution build such as "8.4.1-1~deb12u1", or a
     * constraint with a typo — is not a reason to refuse a module: the answer would be "no" for
     * everyone on that platform, and nothing about the module is actually wrong.
     */
    private function satisfies(string $version, string $constraint): bool
    {
        try {
            return Semver::satisfies($version, $constraint);
        } catch (Throwable) {
            return true;
        }
    }
}
