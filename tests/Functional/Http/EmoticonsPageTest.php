<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Johncms\Smilies\SmiliesMap;
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

    public function testOpeningThePageBuildsAnOutdatedList(): void
    {
        $cache = CACHE_PATH . SmiliesMap::CACHE_FILE;
        $saved = is_file($cache) ? file_get_contents($cache) : null;
        // A cache older than every folder: as if a picture was put in after it was built.
        file_put_contents($cache, serialize(['format' => 0]));
        touch($cache, 1);

        try {
            $content = (string) $this->handleRequest('/admin/emoticons', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()))->getContent();

            self::assertStringContainsString('alert-success', $content);
            self::assertGreaterThan(1, (int) filemtime($cache));
        } finally {
            $saved === null ? @unlink($cache) : file_put_contents($cache, $saved);
        }
    }
}
