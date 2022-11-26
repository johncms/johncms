<?php

declare(strict_types=1);

namespace Johncms\Modules\Data;

use Composer\Semver\Comparator;
use Illuminate\Support\Arr;
use Johncms\Modules\Modules;

class ModuleMetaData
{
    public function __construct(
        public string $name,
        public string $description,
        public string $homepage,
        public string $license,
        public array $authors,
        public bool $isSystem,
        public ?string $version,
        public ?string $repoVersion = null,
        public bool $updateAvailable = false,
    ) {
    }

    public static function createFromComposerJson(array $composerConfig): ModuleMetaData
    {
        $name = (string) Arr::get($composerConfig, 'name', '');
        $authors = Arr::get($composerConfig, 'authors', []);
        $preparedAuthors = [];
        foreach ($authors as $author) {
            $preparedAuthors[] = new Author(
                name:     (string) Arr::get($author, 'name', ''),
                email:    (string) Arr::get($author, 'email', ''),
                homepage: (string) Arr::get($author, 'homepage', ''),
                role:     (string) Arr::get($author, 'role', ''),
            );
        }

        $version = Modules::getModuleVersion($name);
        $repoVersion = Modules::getRepoModuleVersion($name);
        if ($repoVersion && $version) {
            $updateAvailable = Comparator::greaterThan($repoVersion, $version);
        }

        return new self(
            name:            $name,
            description:     (string) Arr::get($composerConfig, 'description', ''),
            homepage:        (string) Arr::get($composerConfig, 'homepage', ''),
            license:         (string) Arr::get($composerConfig, 'license', ''),
            authors:         $preparedAuthors,
            isSystem:        in_array($name, Modules::getSystemModules()),
            version:         $version,
            repoVersion:     $repoVersion,
            updateAvailable: $updateAvailable ?? false,
        );
    }
}
