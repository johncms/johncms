<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Admin\Application\DTO\UserDeletionContextDTO;
use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;

/**
 * Guard and context of deleting an account: checks that the target is a valid one and returns
 * what the confirmation prints. Who may open the screen at all is decided by
 * SuperAdminAccessMiddleware; what is checked here is the target.
 */
final readonly class GetUserDeletionContextUseCase
{
    public function __construct(
        private UserDeletionRepositoryInterface $repository,
        private CurrentUser $currentUser,
        private RoleLevels $roleLevels,
    ) {
    }

    /**
     * @throws WrongUserDataException
     * @throws UserNotFoundException
     * @throws CannotDeleteHigherRightsException
     */
    public function execute(int $id): UserDeletionContextDTO
    {
        if ($id <= 0 || $id === $this->currentUser->id()) {
            throw new WrongUserDataException();
        }

        $user = $this->repository->findById($id);
        if ($user === null) {
            throw new UserNotFoundException();
        }

        // Nobody deletes an account standing above their own: the roles decide that now, the
        // same way they decide who may hand out which role.
        if ($this->roleLevels->highestGrantedTo($id) > $this->roleLevels->highest($this->currentUser->identity())) {
            throw new CannotDeleteHigherRightsException();
        }

        return new UserDeletionContextDTO(
            user: $user,
            commentsCount: $this->repository->countComments($id),
            forumTopicsCount: $this->repository->countForumTopics($id),
            forumPostsCount: $this->repository->countForumPosts($id),
        );
    }
}
