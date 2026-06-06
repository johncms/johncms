<?php

declare(strict_types=1);

namespace Johncms\Modules\Album\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Album\Application\DTO\EditAlbumContextDTO;
use Johncms\Modules\Album\Application\DTO\SaveAlbumCommand;
use Johncms\Modules\Album\Application\Exceptions\AlbumEditForbiddenException;
use Johncms\Modules\Album\Application\Exceptions\AlbumNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumOwnerNotFoundException;
use Johncms\Modules\Album\Application\Exceptions\AlbumValidationException;
use Johncms\Modules\Album\Application\UseCases\GetEditAlbumContextUseCase;
use Johncms\Modules\Album\Application\UseCases\SaveAlbumUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class EditAlbumController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private GetEditAlbumContextUseCase $getContextUseCase,
        private SaveAlbumUseCase $saveAlbumUseCase,
    ) {
        $this->controllerContext->initModule('album');
    }

    public function createForm(int $id): string
    {
        $context = $this->resolveCreateContext($id);
        if (is_string($context)) {
            return $context;
        }

        return $this->renderForm($context, $this->emptyFormData(), []);
    }

    public function createSave(int $id): string
    {
        $context = $this->resolveCreateContext($id);
        if (is_string($context)) {
            return $context;
        }

        return $this->handleSave($context);
    }

    public function editForm(int $al): string
    {
        $context = $this->resolveEditContext($al);
        if (is_string($context)) {
            return $context;
        }

        return $this->renderForm($context, $this->formDataFromAlbum($context), []);
    }

    public function editSave(int $al): string
    {
        $context = $this->resolveEditContext($al);
        if (is_string($context)) {
            return $context;
        }

        return $this->handleSave($context);
    }

    private function handleSave(EditAlbumContextDTO $context): string
    {
        $command = $this->buildCommand();

        try {
            $this->saveAlbumUseCase->execute($command, $context);
        } catch (AlbumValidationException $e) {
            return $this->renderForm($context, $command->toFormData(), $e->errors);
        }

        return $this->render->render(
            'system::pages/result',
            [
                'title'    => $this->title($context),
                'type'     => 'alert-success',
                'message'  => $context->isEdit() ? __('Album successfully changed') : __('Album successfully created'),
                'back_url' => '/album/user/' . $context->ownerId,
            ]
        );
    }

    /**
     * @return EditAlbumContextDTO|string
     */
    private function resolveCreateContext(int $id): EditAlbumContextDTO|string
    {
        try {
            return $this->getContextUseCase->forCreate($id);
        } catch (AlbumOwnerNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    /**
     * @return EditAlbumContextDTO|string
     */
    private function resolveEditContext(int $al): EditAlbumContextDTO|string
    {
        try {
            return $this->getContextUseCase->forEdit($al);
        } catch (AlbumNotFoundException $e) {
            return $this->renderError($e->getMessage());
        } catch (AlbumEditForbiddenException $e) {
            http_response_code(403);
            return $this->renderError($e->getMessage());
        }
    }

    private function buildCommand(): SaveAlbumCommand
    {
        return new SaveAlbumCommand(
            name: (string) $this->request->getPost('name', ''),
            description: (string) $this->request->getPost('description', ''),
            password: (string) $this->request->getPost('password', ''),
            access: (int) $this->request->getPost('access', 0, FILTER_VALIDATE_INT),
        );
    }

    /**
     * @return array{name: string, description: string, password: string, access: int}
     */
    private function emptyFormData(): array
    {
        return [
            'name'        => '',
            'description' => '',
            'password'    => '',
            'access'      => 4,
        ];
    }

    /**
     * @return array{name: string, description: string, password: string, access: int}
     */
    private function formDataFromAlbum(EditAlbumContextDTO $context): array
    {
        $album = $context->album;

        return [
            'name'        => $album->name,
            'description' => $album->description,
            'password'    => (string) $album->password,
            'access'      => (int) $album->access,
        ];
    }

    /**
     * @param array{name: string, description: string, password: string, access: int} $formData
     * @param list<string> $errors
     */
    private function renderForm(EditAlbumContextDTO $context, array $formData, array $errors): string
    {
        $title = $this->title($context);
        $actionUrl = $context->isEdit()
            ? '/album/' . $context->album->id . '/edit'
            : '/album/user/' . $context->ownerId . '/create';

        $this->navChain->add(__('Albums'), '/album');
        $userAlbumsLabel = $context->ownerId === $this->currentUser->id ? __('Your albums') : __('User albums');
        $this->navChain->add($userAlbumsLabel, '/album/user/' . $context->ownerId);
        if ($context->isEdit()) {
            $this->navChain->add($context->album->name, '/album/' . $context->album->id);
        }
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'album::album_form',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => [
                    'error_message' => $errors,
                    'action_url'    => $actionUrl,
                    'back_url'      => '/album/user/' . $context->ownerId,
                    'form_data'     => $formData,
                ],
            ]
        );
    }

    private function title(EditAlbumContextDTO $context): string
    {
        return $context->isEdit() ? __('Edit Album') : __('Create Album');
    }

    private function renderError(string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'   => __('Albums'),
                'type'    => 'alert-danger',
                'message' => $message,
            ]
        );
    }
}
