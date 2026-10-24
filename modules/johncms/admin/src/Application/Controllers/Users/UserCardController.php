<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers\Users;

use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Services\UserCardMapper;
use Johncms\Modules\Admin\Application\UseCases\GetUserCardUseCase;
use Johncms\NavChain;

/**
 * The page of one account in the panel. It stands on its own: the profile of the site is a module
 * that may be switched off, so the panel shows the account itself rather than linking there.
 */
final readonly class UserCardController
{
    public function __construct(
        private NavChain $navChain,
        private GetUserCardUseCase $getCard,
        private UserCardMapper $mapper,
        private Session $session,
    ) {
    }

    public function __invoke(int $id): ViewResponse
    {
        try {
            $card = $this->getCard->execute($id);
        } catch (UserNotFoundException) {
            return new ViewResponse(
                '@admin/pages/result.twig',
                [
                    'title'      => __('Users'),
                    'page_title' => __('Users'),
                    'type'       => 'alert-danger',
                    'message'    => __('User does not exists'),
                    'back_url'   => '/admin/users',
                ],
                404
            );
        }

        $this->navChain->add(__('Users'), '/admin/users');
        $this->navChain->add($card->user->name);

        return new ViewResponse(
            '@admin/user/view.twig',
            [
                'title'           => $card->user->name,
                'page_title'      => $card->user->name,
                'usr_menu'        => ['userlist' => true],
                'user'            => $this->mapper->map($card),
                'tabs'            => $this->mapper->tabs($card->user, 'view'),
                'success_message' => (string) $this->session->getFlash('success_message'),
            ]
        );
    }
}
