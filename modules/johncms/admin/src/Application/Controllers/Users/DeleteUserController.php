<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Application\Services\UserListControlsMapper;
use Johncms\Modules\Admin\Application\UseCases\DeleteUserUseCase;
use Johncms\Modules\Admin\Application\UseCases\GetUserDeletionContextUseCase;
use Johncms\NavChain;

/**
 * Deleting an account. The confirmation is a dialog: the modal of the panel asks for it with
 * X-Requested-With and gets the dialog alone, a plain visit — from the profile, or without the
 * script — gets it as a page.
 *
 * The list of users sends where it was in "back", so the form returns to the same page of the
 * same filter.
 */
final readonly class DeleteUserController
{
    private const LIST_URL = UserListControlsMapper::PATH;

    public function __construct(
        private NavChain $navChain,
        private GetUserDeletionContextUseCase $getContext,
        private DeleteUserUseCase $deleteUser,
        private UserListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function index(Request $request, int $id): ViewResponse
    {
        $title = __('Delete user');
        $dialog = $request->isXmlHttpRequest();

        try {
            $context = $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e), $dialog);
        }

        $data = [
            'user_id'        => $context->user->id,
            'user_name'      => $context->user->name,
            'comments_count' => $context->commentsCount,
            'forum_topics'   => $context->forumTopicsCount,
            'forum_posts'    => $context->forumPostsCount,
            'form_action'    => '/admin/users/' . $context->user->id . '/delete',
            'back_url'       => $this->controls->returnUrl($request->queryParam('back'), '/profile/' . $context->user->id),
        ];

        if ($dialog) {
            return new ViewResponse('@admin/users/delete-dialog.twig', $data);
        }

        $this->navChain->add($title);

        return new ViewResponse(
            '@admin/user-delete-confirm.twig',
            [
                // The dialog carries the heading itself.
                'title'  => $title,
                'dialog' => $data,
            ]
        );
    }

    public function delete(Request $request, int $id): ViewResponse
    {
        $title = __('Delete user');

        try {
            $this->getContext->execute($id);
        } catch (WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e) {
            return $this->renderError($title, $this->errorMessage($e), false);
        }

        $this->deleteUser->execute(
            $id,
            $request->hasBody('comments'),
            $request->hasBody('forum'),
        );

        $this->session->flash('success_message', __('User deleted'));

        redirect($this->controls->returnUrl($request->body('back'), self::LIST_URL));
    }

    private function errorMessage(WrongUserDataException | UserNotFoundException | CannotDeleteHigherRightsException $e): string
    {
        return match (true) {
            $e instanceof UserNotFoundException            => __('User does not exists'),
            $e instanceof CannotDeleteHigherRightsException => __('You cannot delete higher administration'),
            default                                         => __('Wrong data'),
        };
    }

    private function renderError(string $title, string $message, bool $dialog): ViewResponse
    {
        $data = [
            'title'    => $title,
            'type'     => 'alert-danger',
            'message'  => $message,
            'back_url' => self::LIST_URL,
        ];

        if ($dialog) {
            return new ViewResponse('@admin/pages/result-dialog.twig', $data);
        }

        $this->navChain->add($title);

        return new ViewResponse('@admin/pages/result.twig', $data + ['page_title' => $title]);
    }
}
