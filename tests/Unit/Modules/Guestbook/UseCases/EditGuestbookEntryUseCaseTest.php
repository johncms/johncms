<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Modules\Guestbook\Application\UseCases\EditGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class EditGuestbookEntryUseCaseTest extends TestCase
{
    public function testUpdatesEntryAndSaves(): void
    {
        $entry = new GuestbookEntry([
            'text'           => 'old text',
            'edit_count'     => 1,
            'attached_files' => [1],
        ]);

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->expects(self::once())->method('save')->with(self::identicalTo($entry));

        $useCase = new EditGuestbookEntryUseCase($repository, UserFactory::make(rights: 3, attributes: ['name' => 'Moderator']));
        $useCase->execute($entry, 'new text', [2, 3]);

        // edit_time/edit_who имеют касты (TimeToDate дёргает di()), поэтому читаем сырые атрибуты
        $attributes = $entry->getAttributes();

        self::assertSame('new text', $entry->text);
        self::assertSame('Moderator', $attributes['edit_who']);
        self::assertEqualsWithDelta(time(), $attributes['edit_time'], 5);
        self::assertSame(2, $entry->edit_count);
        self::assertSame([1, 2, 3], $entry->attached_files);
    }

    public function testKeepsExistingFilesWhenNothingAttached(): void
    {
        $entry = new GuestbookEntry([
            'text'           => 'old text',
            'edit_count'     => 0,
            'attached_files' => [7],
        ]);

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->expects(self::once())->method('save');

        $useCase = new EditGuestbookEntryUseCase($repository, UserFactory::make(rights: 3));
        $useCase->execute($entry, 'edited', []);

        self::assertSame([7], $entry->attached_files);
        self::assertSame(1, $entry->edit_count);
    }
}
