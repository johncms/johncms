<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\UseCases;

use Johncms\Modules\Album\Application\DTO\EditAlbumContextDTO;
use Johncms\Modules\Album\Application\DTO\SaveAlbumCommand;
use Johncms\Modules\Album\Application\Exceptions\AlbumValidationException;
use Johncms\Modules\Album\Domain\Repository\AlbumPhotoRepositoryInterface;
use Johncms\Modules\Album\Domain\Repository\AlbumRepositoryInterface;

final readonly class SaveAlbumUseCase
{
    public function __construct(
        private AlbumRepositoryInterface $albumRepository,
        private AlbumPhotoRepositoryInterface $photoRepository,
    ) {
    }

    public function execute(SaveAlbumCommand $command, EditAlbumContextDTO $context): void
    {
        $name = trim($command->name);
        $description = mb_substr(trim($command->description), 0, 500);
        $password = trim($command->password);
        $access = $command->access;

        $errors = [];

        $nameLength = mb_strlen($name);
        if ($nameLength < 2 || $nameLength > 150) {
            $errors[] = __('Title') . ': ' . __('Invalid length');
        }

        if ($access === 2 && $password === '') {
            $errors[] = __('You have not entered password');
        } elseif (($access === 2 && mb_strlen($password) < 3) || mb_strlen($password) > 15) {
            $errors[] = __('Password') . ': ' . __('Invalid length');
        }

        if ($access < 1 || $access > 4) {
            $errors[] = __('Wrong data');
        }

        // The album name must be unique within the owner's albums (only checked on create).
        if (! $context->isEdit() && $this->albumRepository->existsByNameForUser($context->ownerId, $name)) {
            $errors[] = __('The album already exists');
        }

        if ($errors !== []) {
            throw new AlbumValidationException($errors);
        }

        if ($context->isEdit()) {
            $album = $context->album;
            // Cascade the album access level to all of its photos.
            $this->photoRepository->setAccessForAlbum($album->id, $access);
            $this->albumRepository->update($album, $name, $description, $password, $access);

            return;
        }

        $this->albumRepository->create($context->ownerId, $name, $description, $password, $access);
    }
}
