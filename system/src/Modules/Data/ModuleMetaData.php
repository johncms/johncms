<?php

declare(strict_types=1);

namespace Johncms\Modules\Data;

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

        return new self(
            name:        $name,
            description: (string) Arr::get($composerConfig, 'description', ''),
            homepage:    (string) Arr::get($composerConfig, 'homepage', ''),
            license:     (string) Arr::get($composerConfig, 'license', ''),
            authors:     $preparedAuthors,
            isSystem:    in_array($name, Modules::getSystemModules()),
        );
    }
}
