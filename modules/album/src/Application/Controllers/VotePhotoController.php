<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\VoteNotAllowedException;
use Johncms\Modules\Album\Application\UseCases\EnsureVoteAccessUseCase;
use Johncms\Modules\Album\Application\UseCases\GetVotePhotoContextUseCase;
use Johncms\Modules\Album\Application\UseCases\VotePhotoUseCase;
use Johncms\Modules\Album\Domain\Enums\VoteType;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class VotePhotoController
{
    public function __construct(
        private GetVotePhotoContextUseCase $getContextUseCase,
        private EnsureVoteAccessUseCase $ensureAccessUseCase,
        private VotePhotoUseCase $votePhotoUseCase,
    ) {
    }

    public function __invoke(int $img, string $type): ViewResponse
    {
        $voteType = VoteType::tryFrom($type);
        if ($voteType === null) {
            return $this->renderError(__('Wrong data'));
        }

        try {
            $photo = $this->getContextUseCase->execute($img);
            $this->ensureAccessUseCase->execute($photo);
        } catch (AlbumPhotoNotFoundException) {
            return $this->renderError(__('Wrong data'));
        } catch (VoteNotAllowedException $e) {
            return $this->renderError($e->getMessage());
        }

        $this->votePhotoUseCase->execute($photo, $voteType);

        redirect('/album/photo/' . $photo->id);
    }

    private function renderError(string $message): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'    => __('Albums'),
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/album',
            ]
        );
    }
}
