<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Modules\Guestbook\Application\UseCases\ReplyToGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\CurrentUserFactory;
use Tests\Support\UserFactory;

final class ReplyToGuestbookEntryUseCaseTest extends TestCase
{
    public function testWritesReplyAndSaves(): void
    {
        $entry = new GuestbookEntry([
            'otvet'          => '',
            'attached_files' => [4],
        ]);

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->expects(self::once())->method('save')->with(self::identicalTo($entry));

        $useCase = new ReplyToGuestbookEntryUseCase($repository, CurrentUserFactory::withProfile(UserFactory::make(attributes: ['name' => 'Admin'])));
        $useCase->execute($entry, 'reply text', [5]);

        // otime имеет каст TimeToDate (дёргает di()), поэтому читаем сырой атрибут
        $attributes = $entry->getAttributes();

        self::assertSame('reply text', $entry->otvet);
        self::assertSame('Admin', $entry->admin);
        self::assertEqualsWithDelta(time(), $attributes['otime'], 5);
        self::assertSame([4, 5], $entry->attached_files);
    }
}
