<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\UseCases;

use Johncms\Config\ConfigRepository;
use Johncms\Modules\Guestbook\Application\DTO\CreateGuestbookEntryDTO;
use Johncms\Modules\Guestbook\Application\UseCases\CreateGuestbookEntryUseCase;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Guestbook\Domain\Repository\GuestbookEntryRepositoryInterface;
use Johncms\Users\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class CreateGuestbookEntryUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init([]);
    }

    public function testCreatesEntryAndRegistersPostForValidUser(): void
    {
        $user = UserFactory::make(attributes: ['id' => 7]);
        $createdEntry = new GuestbookEntry();
        $capturedAttributes = null;

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('create')
            ->willReturnCallback(function (array $attributes) use (&$capturedAttributes, $createdEntry) {
                $capturedAttributes = $attributes;
                return $createdEntry;
            });

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects(self::once())->method('registerGuestbookPost')->with(self::identicalTo($user));

        $useCase = new CreateGuestbookEntryUseCase($repository, $userRepository, $user);
        $result = $useCase->execute(new CreateGuestbookEntryDTO(
            adminClub:     true,
            name:          'TestUser',
            text:          'Hello!',
            ip:            '127.0.0.1',
            userAgent:     'TestBrowser',
            attachedFiles: [5],
        ));

        self::assertSame($createdEntry, $result);
        self::assertTrue($capturedAttributes['adm']);
        self::assertEqualsWithDelta(time(), $capturedAttributes['time'], 5);
        self::assertSame(7, $capturedAttributes['user_id']);
        self::assertSame('TestUser', $capturedAttributes['name']);
        self::assertSame('Hello!', $capturedAttributes['text']);
        self::assertSame('127.0.0.1', $capturedAttributes['ip']);
        self::assertSame('TestBrowser', $capturedAttributes['browser']);
        self::assertSame('', $capturedAttributes['otvet']);
        self::assertSame([5], $capturedAttributes['attached_files']);
    }

    public function testGuestEntryDoesNotUpdateUserStats(): void
    {
        $guest = UserFactory::make(valid: false);
        $capturedAttributes = null;

        $repository = $this->createMock(GuestbookEntryRepositoryInterface::class);
        $repository->method('create')->willReturnCallback(function (array $attributes) use (&$capturedAttributes) {
            $capturedAttributes = $attributes;
            return new GuestbookEntry();
        });

        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects(self::never())->method('registerGuestbookPost');

        $useCase = new CreateGuestbookEntryUseCase($repository, $userRepository, $guest);
        $useCase->execute(new CreateGuestbookEntryDTO(
            adminClub:     false,
            name:          'Guest',
            text:          'Hi',
            ip:            '10.0.0.1',
            userAgent:     'UA',
            attachedFiles: [],
        ));

        self::assertSame(0, $capturedAttributes['user_id']);
        self::assertFalse($capturedAttributes['adm']);
        self::assertSame('Guest', $capturedAttributes['name']);
    }
}
