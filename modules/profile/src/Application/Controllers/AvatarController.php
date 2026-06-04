<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\DTO\EditProfileContextDTO;
use Johncms\Modules\Profile\Application\Exceptions\ImageUploadException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileAccessForbiddenException;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\UseCases\GetEditContextUseCase;
use Johncms\Modules\Profile\Application\UseCases\UploadAvatarUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;

final readonly class AvatarController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetEditContextUseCase $getEditContextUseCase,
        private UploadAvatarUseCase $uploadAvatarUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function form(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }

        return $this->renderForm($context);
    }

    public function upload(int $id): string
    {
        $context = $this->resolveContext($id);
        if (is_string($context)) {
            return $context;
        }

        $file = $this->request->getUploadedFiles()['imagefile'] ?? null;

        try {
            if ($file === null) {
                throw new ImageUploadException(__('An error occurred'));
            }
            $this->uploadAvatarUseCase->execute($context->profileUser->id, $file);
        } catch (ImageUploadException $e) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Upload Avatar'),
                    'type'          => 'alert-danger',
                    'message'       => $e->getMessage(),
                    'back_url'      => '/profile/' . $context->profileUser->id . '/edit/avatar',
                    'back_url_name' => __('Repeat'),
                ]
            );
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Upload Avatar'),
                'type'          => 'alert-success',
                'message'       => __('The avatar is successfully uploaded'),
                'back_url'      => '/profile/' . $context->profileUser->id . '/edit',
                'back_url_name' => __('Continue'),
            ]
        );
    }

    private function renderForm(EditProfileContextDTO $context): string
    {
        $title = __('Upload Avatar');

        $this->navChain->add($context->isSelf ? __('My Profile') : __('Profile'), '/profile/' . $context->profileUser->id);
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::images',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'form_action' => '/profile/' . $context->profileUser->id . '/edit/avatar',
                    'back_url'    => '/profile/' . $context->profileUser->id,
                ],
            ]
        );
    }

    /**
     * @return EditProfileContextDTO|string
     */
    private function resolveContext(int $id): EditProfileContextDTO|string
    {
        try {
            return $this->getEditContextUseCase->execute($id);
        } catch (ProfileNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (ProfileAccessForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Upload Avatar'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }
}
