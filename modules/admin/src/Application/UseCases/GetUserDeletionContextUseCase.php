<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\Admin\Application\DTO\UserDeletionContextDTO;
use Johncms\Modules\Admin\Application\Exceptions\CannotDeleteHigherRightsException;
use Johncms\Modules\Admin\Application\Exceptions\UserNotFoundException;
use Johncms\Modules\Admin\Application\Exceptions\WrongUserDataException;
use Johncms\Modules\Admin\Domain\Repository\UserDeletionRepositoryInterface;
use Johncms\Users\User;

/**
 * Guard + context для удаления пользователя: проверяет права/корректность цели
 * и возвращает данные подтверждения (счётчики активности). Доступ rights>=9
 * обеспечивает SuperAdminAccessMiddleware; здесь — точечные проверки цели.
 */
final readonly class GetUserDeletionContextUseCase
{
    public function __construct(
        private UserDeletionRepositoryInterface $repository,
        private User $currentUser,
    ) {
    }

    /**
     * @throws WrongUserDataException
     * @throws UserNotFoundException
     * @throws CannotDeleteHigherRightsException
     */
    public function execute(int $id): UserDeletionContextDTO
    {
        if ($id <= 0 || $id === $this->currentUser->id) {
            throw new WrongUserDataException();
        }

        $user = $this->repository->findById($id);
        if ($user === null) {
            throw new UserNotFoundException();
        }

        if ($user->rights > $this->currentUser->rights) {
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
