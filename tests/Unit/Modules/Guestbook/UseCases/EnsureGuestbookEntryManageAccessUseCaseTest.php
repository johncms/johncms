<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Modules\Guestbook\Application\Exceptions\GuestbookAccessDeniedException;
use Johncms\Modules\Guestbook\Application\UseCases\EnsureGuestbookEntryManageAccessUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Users\User;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class EnsureGuestbookEntryManageAccessUseCaseTest extends TestCase
{
    public function testThrowsWhenCurrentUserHasNoRights(): void
    {
        $useCase = new EnsureGuestbookEntryManageAccessUseCase(UserFactory::make(rights: 0));

        $this->expectException(GuestbookAccessDeniedException::class);
        $useCase->execute($this->makeEntry(null));
    }

    public function testThrowsWhenAuthorHasHigherRights(): void
    {
        $useCase = new EnsureGuestbookEntryManageAccessUseCase(UserFactory::make(rights: 3));

        $this->expectException(GuestbookAccessDeniedException::class);
        $useCase->execute($this->makeEntry(UserFactory::make(rights: 9)));
    }

    public function testAllowsGuestEntry(): void
    {
        $this->expectNotToPerformAssertions();

        $useCase = new EnsureGuestbookEntryManageAccessUseCase(UserFactory::make(rights: 1));
        $useCase->execute($this->makeEntry(null));
    }

    public function testAllowsAuthorWithEqualOrLowerRights(): void
    {
        $this->expectNotToPerformAssertions();

        $useCase = new EnsureGuestbookEntryManageAccessUseCase(UserFactory::make(rights: 3));
        $useCase->execute($this->makeEntry(UserFactory::make(rights: 3)));
        $useCase->execute($this->makeEntry(UserFactory::make(rights: 1)));
    }

    private function makeEntry(?User $author): GuestbookEntry
    {
        $entry = new GuestbookEntry();
        $entry->setRelation('user', $author);

        return $entry;
    }
}
