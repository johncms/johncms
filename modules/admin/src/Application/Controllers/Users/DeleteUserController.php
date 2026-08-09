<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Application\UseCases\DeleteUserUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetUserDeletionContextUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Validator\Validator;

final readonly class DeleteUserController
{
    public function __construct(
        private NavChain $navChain,
        private GetUserDeletionContextUseCase $getContext,
        private DeleteUserUseCase $deleteUser,
    ) {
    }

    public function index(int $id): ViewResponse
    {
        $title = __('Delete user');
        $this->navChain->add($title);

        try {
            $context = $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e));
        }

        return new ViewResponse(
            '@admin/user-delete-confirm.twig',
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

    public function delete(Request $request, int $id): ViewResponse
    {
        $title = __('Delete user');
        $this->navChain->add($title);

        if (! $this->isCsrfValid($request)) {
            return $this->renderError($title, __('Wrong data'));
        }

        try {
            $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e));
        }

        $this->deleteUser->execute(
            $id,
            $request->hasBody('comments'),
            $request->hasBody('forum'),
        );

        return new ViewResponse(
            '@admin/pages/result.twig',
            [
                'title'    => $title,
                'page_title' => $title,
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

    private function isCsrfValid(Request $request): bool
    {
        $validator = new Validator(
            ['csrf_token' => $request->body('csrf_token', '')],
            ['csrf_token' => ['Csrf']]
        );

        return $validator->isValid();
    }

    private function renderError(string $title, string $message): ViewResponse
    {
        return new ViewResponse(
            '@admin/pages/result.twig',
            [
                'title'    => $title,
                'page_title' => $title,
                'type'     => 'alert-danger',
                'message'  => $message,
                'back_url' => '/admin/users',
            ]
        );
    }
}
