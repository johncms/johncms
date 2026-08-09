<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Http\UploadedFileMapper;
use Johncms\Modules\Profile\Application\DTO\EditProfileContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetEditContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\UploadAvatarUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final readonly class AvatarController
{
    public function __construct(
        private NavChain $navChain,
        private GetEditContextUseCase $getEditContextUseCase,
        private UploadAvatarUseCase $uploadAvatarUseCase,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function form(int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        return $this->renderForm($context);
    }

    public function upload(Request $request, int $id): ViewResponse
    {
        $context = $this->resolveContext($id);
        if ($context instanceof ViewResponse) {
            return $context;
        }

        $uploaded = $request->files->get('imagefile');

        try {
            if (! $uploaded instanceof UploadedFile) {
                throw new ImageUploadException(__('An error occurred'));
            }
            $this->uploadAvatarUseCase->execute($context->profileUser->id, $this->uploadedFileMapper->fromUploadedFile($uploaded));
        } catch (ImageUploadException $e) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('Upload Avatar'),
                    'type'          => 'alert-danger',
                    'message'       => $e->getMessage(),
                    'back_url'      => '/profile/' . $context->profileUser->id . '/edit/avatar',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'         => __('Upload Avatar'),
                'type'          => 'alert-success',
                'message'       => __('The avatar is successfully uploaded'),
                'back_url'      => '/profile/' . $context->profileUser->id . '/edit',
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function renderForm(EditProfileContextDTO $context): ViewResponse
    {
        $title = __('Upload Avatar');

        $this->navChain->add($context->isSelf ? __('My Profile') : __('Profile'), '/profile/' . $context->profileUser->id);
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/images.twig',
            [
                'max_file_size' => (int) config('johncms.flsz'),
                'title'         => $title,
                'page_title'    => $title,
                'form_action'   => '/profile/' . $context->profileUser->id . '/edit/avatar',
                'back_url'      => '/profile/' . $context->profileUser->id,
            ]
        );
    }

    private function resolveContext(int $id): EditProfileContextDTO|ViewResponse
    {
        try {
            return $this->getEditContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            return $this->renderError($e->getMessage(), 403);
        }
    }

    private function renderError(string $message, int $status = 200): ViewResponse
    {
        return new ViewResponse(
            '@theme/pages/result.twig',
            [
                'title'   => __('Upload Avatar'),
                'type'    => 'alert-danger',
                'message' => $message,
            ],
            $status
        );
    }
}
