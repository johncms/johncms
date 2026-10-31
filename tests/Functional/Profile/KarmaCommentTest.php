<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Dom\HTMLDocument;
use Johncms\Users\Karma;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class KarmaCommentTest extends FunctionalTestCase
{
    #[DataProvider('lists')]
    public function testSmiliesRenderAsImagesWhileTheCommentRemainsPlainText(string $suffix): void
    {
        $owner = FunctionalUserFactory::create();
        $voter = FunctionalUserFactory::create();
        $text = 'Thanks :) <b>plain</b> & "quotes" <script>alert("bad")</script>';

        Karma::query()->create([
            'user_id'    => $voter->id,
            'name'       => $voter->name,
            'karma_user' => $owner->id,
            'points'     => 1,
            'type'       => 1,
            'time'       => time(),
            'text'       => $text,
        ]);

        $response = $this->withConfig(
            ['johncms' => ['karma' => ['on' => 1]]],
            fn (): Response => $this->handleRequest('/profile/' . $owner->id . '/karma' . $suffix, cookies: $this->actingAs($owner))
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        $comment = $page->querySelector('.user-row .pt-1');
        self::assertNotNull($comment);
        self::assertCount(1, $comment->querySelectorAll('img[src*="/assets/emoticons/"]'));
        self::assertCount(0, $comment->querySelectorAll('b, script'));
        self::assertStringContainsString('<b>plain</b> & "quotes" <script>alert("bad")</script>', $comment->textContent);
        self::assertStringNotContainsString('<img', $comment->textContent);
        self::assertSame($text, Karma::query()->firstOrFail()->text);
    }

    public static function lists(): iterable
    {
        yield 'all votes' => ['?type=2'];
        yield 'new responses' => ['/new'];
    }
}
