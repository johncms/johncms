<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use HTMLPurifier;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\System\Http\Session;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Users\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Simba77\EmbedMedia\Embed;
use Tests\Support\UserFactory;

final class ListGuestbookEntriesUseCaseTest extends TestCase
{
    private GuestbookEntryRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $_SESSION = [];
        ConfigRepository::init([]);
        // rights_name дёргает d__() — нужен зарегистрированный переводчик (возвращает оригиналы)
        TranslatorFunctions::register(new Translator());
        $this->repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testCountPassesAdminClubModeToRepository(): void
    {
        $_SESSION['ga'] = 1;

        $this->repository->expects(self::once())->method('countEntries')->with(true)->willReturn(42);

        $useCase = $this->makeUseCase(UserFactory::make(rights: 1));

        self::assertSame(42, $useCase->count());
    }

    public function testGetPagePassesModeAndSlicingToRepository(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getEntries')
            ->with(false, 10, 20)
            ->willReturn(new Collection());

        $useCase = $this->makeUseCase(UserFactory::make(rights: 1));

        self::assertSame([], $useCase->getPage(10, 20));
    }

    public function testMapsEntryToDtoWithoutMetaForRegularUser(): void
    {
        $author = UserFactory::make(rights: 9, attributes: ['id' => 5, 'name' => 'Author', 'status' => 'The Boss']);
        $entry = $this->makeEntry(['user_id' => 5, 'name' => 'Author', 'text' => 'Hello', 'otvet' => 'Reply'], $author);

        $this->repository->method('getEntries')->willReturn(new Collection([$entry]));

        $dtos = $this->makeUseCase(UserFactory::make(rights: 0))->getPage(10, 0);

        self::assertCount(1, $dtos);
        $dto = $dtos[0];

        self::assertSame(10, $dto->id);
        self::assertSame('Author', $dto->name);
        self::assertTrue($dto->isOnline);
        self::assertSame('Hello', $dto->text);
        self::assertSame('Reply', $dto->replyText);
        self::assertSame(5, $dto->userId);

        self::assertNotNull($dto->user);
        self::assertSame(5, $dto->user->id);
        self::assertSame('/profile/5', $dto->user->profileUrl);
        self::assertSame('Supervisor', $dto->user->rightsName);
        self::assertSame(9, $dto->user->rights);
        self::assertSame('The Boss', $dto->user->status);

        self::assertNull($dto->meta);
    }

    public function testMetaDeniesManagingEntryOfHigherRankedAuthor(): void
    {
        $author = UserFactory::make(rights: 9, attributes: ['id' => 5, 'status' => '']);
        $entry = $this->makeEntry(['user_id' => 5], $author);

        $this->repository->method('getEntries')->willReturn(new Collection([$entry]));

        $dto = $this->makeUseCase(UserFactory::make(rights: 6))->getPage(10, 0)[0];

        self::assertNotNull($dto->meta);
        self::assertSame('127.0.0.1', $dto->meta->ip);
        self::assertSame('/admin/ip-search?ip=127.0.0.1', $dto->meta->searchIpUrl);
        self::assertSame('UA', $dto->meta->userAgent);
        self::assertFalse($dto->meta->canManage);
        self::assertNull($dto->meta->editUrl);
        self::assertNull($dto->meta->deleteUrl);
        self::assertNull($dto->meta->replyUrl);
    }

    public function testMetaAllowsManagingGuestEntry(): void
    {
        $entry = $this->makeEntry([], null);

        $this->repository->method('getEntries')->willReturn(new Collection([$entry]));

        $dto = $this->makeUseCase(UserFactory::make(rights: 1))->getPage(10, 0)[0];

        self::assertNull($dto->user);
        self::assertFalse($dto->isOnline);

        self::assertNotNull($dto->meta);
        self::assertTrue($dto->meta->canManage);
        self::assertSame('/guestbook/edit?id=10', $dto->meta->editUrl);
        self::assertSame('/guestbook/delpost?id=10', $dto->meta->deleteUrl);
        self::assertSame('/guestbook/otvet?id=10', $dto->meta->replyUrl);
    }

    private function makeUseCase(User $currentUser): ListGuestbookEntriesUseCase
    {
        return new ListGuestbookEntriesUseCase(
            $this->repository,
            $currentUser,
            new GuestbookMode($currentUser, new Session()),
            $this->makeTextFormatter(),
        );
    }

    private function makeTextFormatter(): GuestbookEntryTextFormatter
    {
        // GuestbookEntryTextFormatter — final, собираем реальный с моками-пассрушниками
        $purifier = $this->createMock(HTMLPurifier::class);
        $purifier->method('purify')->willReturnArgument(0);

        $media = $this->createMock(Embed::class);
        $media->method('embedMedia')->willReturnArgument(0);

        $smiliesRenderer = $this->createMock(SmiliesRendererInterface::class);
        $smiliesRenderer->method('render')->willReturnArgument(0);

        return new GuestbookEntryTextFormatter($purifier, $media, $smiliesRenderer);
    }

    /**
     * Касты TimeToDate дёргают di() при непустом значении, поэтому time/otime/edit_time — пустые.
     *
     * @param array<string, mixed> $attributes
     */
    private function makeEntry(array $attributes, ?User $author): GuestbookEntry
    {
        $entry = new GuestbookEntry(array_merge([
            'time'           => '',
            'user_id'        => 0,
            'name'           => 'Guest',
            'text'           => 'Hello',
            'ip'             => '127.0.0.1',
            'browser'        => 'UA',
            'admin'          => '',
            'otvet'          => '',
            'otime'          => 0,
            'edit_who'       => '',
            'edit_time'      => 0,
            'edit_count'     => 0,
            'attached_files' => [],
        ], $attributes));
        $entry->id = 10;
        $entry->setRelation('user', $author);

        return $entry;
    }
}
