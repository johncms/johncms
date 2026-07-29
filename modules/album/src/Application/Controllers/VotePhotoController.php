<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\Exceptions\AlbumPhotoNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\VoteNotAllowedException;
use Johncms\Modules\Album\Application\UseCases\EnsureVoteAccessUseCase;
use Johncms\Modules\Album\Application\UseCases\GetVotePhotoContextUseCase;
use Johncms\Modules\Album\Application\UseCases\VotePhotoUseCase;
use Johncms\Modules\Album\Domain\Enums\VoteType;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class VotePhotoController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private GetVotePhotoContextUseCase $getContextUseCase,
        private EnsureVoteAccessUseCase $ensureAccessUseCase,
        private VotePhotoUseCase $votePhotoUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function __invoke(Request $request, int $img, string $type): string
    {
        $voteType = VoteType::tryFrom($type);
        if ($voteType === null) {
            return $this->renderError(__('Wrong data'));
        }

        if (! $this->isCsrfValid($request)) {
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

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => __('Albums'),
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/album',
            ]
        );
    }
}
