<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class EmoticonsPageTest extends FunctionalTestCase
{
    public function testTheFoldersOfTheSmiliesAreShownWithTheirPictures(): void
    {
        $response = $this->handleRequest('/admin/emoticons', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<code class="text-body">simply</code>', $content);
        self::assertStringContainsString('<code class="text-body">admin</code>', $content);
        self::assertMatchesRegularExpression('#<code class="text-body">user/[a-z_]+</code>#', $content);
        self::assertStringContainsString('src="/assets/emoticons/simply/', $content);
        self::assertStringContainsString('href="/help/smilies"', $content);
    }
}
