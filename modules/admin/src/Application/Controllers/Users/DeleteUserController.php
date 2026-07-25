<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Application\UseCases\DeleteUserUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetUserDeletionContextUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class DeleteUserController
{
    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private GetUserDeletionContextUseCase $getContext,
        private DeleteUserUseCase $deleteUser,
    ) {
        $this->controllerContext->initModule('admin');
    }

    public function index(int $id): string
    {
        $title = __('Delete user');
        $this->navChain->add($title);

        try {
            $context = $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e));
        }

        return $this->render->render(
            'admin::usr_del',
            [
                'title'            => $title,
                'page_title'       => $title,
                'user_id'          => $context->user->id,
                'user_name'        => $context->user->name,
                'comments_count'   => $context->commentsCount,
                'forum_topics'     => $context->forumTopicsCount,
                'forum_posts'      => $context->forumPostsCount,
                'form_action'      => '/admin/users/' . $context->user->id . '/delete',
                'back_url'         => '/profile/' . $context->user->id,
            ]
        );
    }

    public function delete(int $id): string
    {
        $title = __('Delete user');
        $this->navChain->add($title);

        if (! $this->isCsrfValid()) {
            return $this->renderError($title, __('Wrong data'));
        }

        try {
            $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e));
        }

        $this->deleteUser->execute(
            $id,
            $this->request->hasBody('comments'),
            $this->request->hasBody('forum'),
        );

        return $this->render->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-success',
                'message'  => __('User deleted'),
                'back_url' => '/admin/users',
            ]
        );
    }

    private function errorMessage(WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e): string
    {
        return match (true) {
            $e instanceof UserNotFoundException            => __('User does not exists'),
            $e instanceof CannotDeleteHigherRightsException => __('You cannot delete higher administration'),
            default                                         => __('Wrong data'),
        };
    }

    private function isCsrfValid(): bool
    {
        $validator = new Validator(
            ['csrf_token' => $this->request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderError(string $title, string $message): string
    {
        return $this->render->render(
            'system::pages/result',
            [
                'title'    => $title,
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/admin/users',
            ]
        );
    }
}
