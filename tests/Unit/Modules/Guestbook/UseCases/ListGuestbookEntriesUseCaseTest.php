<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\Authorization\RoleLevels;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Config\ConfigRepository;
use Johncms\Content\ContentRendererInterface;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Modules\Guestbook\Application\Access\GuestbookMode;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Application\UseCases\ListGuestbookEntriesUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Support\FakeAccessChecker;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;
use Tests\Support\IdentityFactory;
use Tests\Support\CurrentUserFactory;
use Tests\Support\UserFactory;
use Twig\Markup;

final class ListGuestbookEntriesUseCaseTest extends TestCase
{
    private GuestbookEntryRepositoryInterface&MockObject $repository;

    private Session $session;

    private FakeRoleRepository $roles;

    protected function setUp(): void
    {
        ConfigRepository::init([]);
        // rights_name дёргает d__() — нужен зарегистрированный переводчик (возвращает оригиналы)
        TranslatorFunctions::register(new Translator());
        $this->repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $this->session = new Session(new MockArraySessionStorage());
        $this->roles = new FakeRoleRepository();
        $this->roles->add('user', level: 10, isDefault: true);
        $this->roles->add('moderator', level: 30);
        $this->roles->add('supervisor', level: 90);
    }

    public function testCountPassesAdminClubModeToRepository(): void
    {
        $this->session->set('ga', 1);

        $this->repository->expects(self::once())->method('countEntries')->with(true)->willReturn(42);

        $useCase = $this->makeUseCase(granted: [GuestbookPermissions::ADMIN_CLUB_VIEW]);

        self::assertSame(42, $useCase->count());
    }

    public function testGetPagePassesModeAndSlicingToRepository(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getEntries')
            ->with(false, 10, 20)
            ->willReturn(new Collection());

        $useCase = $this->makeUseCase();

        self::assertSame([], $useCase->getPage(10, 20));
    }

    public function testMapsEntryToDtoWithoutMetaForRegularUser(): void
    {
        $author = UserFactory::make(attributes: ['id' => 5, 'name' => 'Author', 'status' => 'The Boss']);
        $this->roles->grantTo(5, ['supervisor']);
        $entry = $this->makeEntry(['user_id' => 5, 'name' => 'Author', 'text' => 'Hello', 'otvet' => 'Reply'], $author);

        $this->repository->method('getEntries')->willReturn(new Collection([$entry]));

        $dtos = $this->makeUseCase()->getPage(10, 0);

        self::assertCount(1, $dtos);
        $dto = $dtos[0];

        self::assertSame(10, $dto->id);
        self::assertSame('Author', $dto->name);
        self::assertTrue($dto->isOnline);
        // Both are markup: the formatter has sanitized them, and a template prints them as they are.
        self::assertSame('Hello', (string) $dto->text);
        self::assertSame('Reply', (string) $dto->replyText);
        self::assertSame(5, $dto->userId);

        self::assertNotNull($dto->user);
        self::assertSame(5, $dto->user->id);
        self::assertSame('/profile/5', $dto->user->profileUrl);
        self::assertSame('Supervisor', $dto->user->rightsName, 'The caption is the role, not a number');
        self::assertSame('The Boss', $dto->user->status);

        self::assertNull($dto->meta);
    }

    public function testMetaDeniesManagingEntryOfAnAuthorStandingAbove(): void
    {
        $author = UserFactory::make(attributes: ['id' => 5, 'status' => '']);
        $entry = $this->makeEntry(['user_id' => 5], $author);
        $this->roles->grantTo(1, ['moderator']);
        $this->roles->grantTo(5, ['supervisor']);

        $this->repository->method('getEntries')->willReturn(new Collection([$entry]));

        $dto = $this->makeUseCase(
            granted: [GuestbookPermissions::ENTRY_MANAGE, CorePermissions::USERS_ORIGIN_VIEW]
        )->getPage(10, 0)[0];

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

        $dto = $this->makeUseCase(
            granted: [GuestbookPermissions::ENTRY_MANAGE, GuestbookPermissions::ENTRY_REPLY]
        )->getPage(10, 0)[0];

        self::assertNull($dto->user);
        self::assertFalse($dto->isOnline);

        self::assertNotNull($dto->meta);
        self::assertTrue($dto->meta->canManage);
        self::assertSame('/guestbook/edit?id=10', $dto->meta->editUrl);
        self::assertSame('/guestbook/delpost?id=10', $dto->meta->deleteUrl);
        self::assertSame('/guestbook/otvet?id=10', $dto->meta->replyUrl);
    }

    /**
     * @param list<string> $granted
     */
    private function makeUseCase(array $granted = []): ListGuestbookEntriesUseCase
    {
        $legacyUser = UserFactory::make(attributes: ['id' => 1]);
        $accessChecker = new FakeAccessChecker($granted);

        $stack = new RequestStack();
        $stack->push(Request::create('/guestbook', 'GET'));
        $currentUser = new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator(IdentityFactory::user(id: 1, roles: ['user', 'moderator']))]),
            new PermissionResolver($this->roles),
            $stack,
            new FakeUserRepository()
        );

        return new ListGuestbookEntriesUseCase(
            $this->repository,
            $currentUser,
            new GuestbookMode(CurrentUserFactory::withProfile($legacyUser), $this->session, $accessChecker),
            $this->makeTextFormatter(),
            $accessChecker,
            new RoleLevels($this->roles),
            new StaffTitles($this->roles),
        );
    }

    private function makeTextFormatter(): GuestbookEntryTextFormatter
    {
        // GuestbookEntryTextFormatter is final, so the real one is built over a pipeline that
        // does nothing but hand the text back: this test is about the listing, not the markup.
        $content = $this->createMock(ContentRendererInterface::class);
        $content->method('render')->willReturnCallback(
            static fn (string $html): Markup => new Markup($html, 'UTF-8')
        );
        $content->method('renderOrNull')->willReturnCallback(
            static fn (string $html): ?Markup => $html === '' ? null : new Markup($html, 'UTF-8')
        );

        return new GuestbookEntryTextFormatter(new StaffTitles($this->roles), $content);
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
