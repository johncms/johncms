<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Api;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsPreviewApi;
use Johncms\Modules\Collections\Application\Exceptions\CollectionPreviewAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;
use PHPUnit\Framework\TestCase;

final class CollectionsPreviewApiTest extends TestCase
{
    public function testDirectApiCallChecksCurrentIdentityBeforeReadingData(): void
    {
        $access = $this->createMock(AccessCheckerInterface::class);
        $access->expects(self::once())->method('allows')->with(CollectionsPermissions::MANAGE)->willReturn(false);
        $api = $this->createMock(CollectionsApiInterface::class);
        $api->expects(self::never())->method('getItem');
        $this->expectException(CollectionPreviewAccessDeniedException::class);
        (new CollectionsPreviewApi($access, $api))->findItem(1, 2);
    }
}
