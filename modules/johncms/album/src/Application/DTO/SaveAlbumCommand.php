<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\DTO;

final readonly class SaveAlbumCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public string $password,
        public int $access,
    ) {
    }

    /**
     * @return array{name: string, description: string, password: string, access: int}
     */
    public function toFormData(): array
    {
        return [
            'name'        => $this->name,
            'description' => $this->description,
            'password'    => $this->password,
            'access'      => $this->access,
        ];
    }
}
