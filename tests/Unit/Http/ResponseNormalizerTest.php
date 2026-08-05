<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\ResponseNormalizer;
use Johncms\Http\View\ViewResponse;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\Unit\View\RecordingRenderer;
use stdClass;

/**
 * Tests for the transitional Response|ViewResponse|string|null contract.
 */
final class ResponseNormalizerTest extends TestCase
{
    private ResponseNormalizer $normalizer;

    private RecordingRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new RecordingRenderer('<html>rendered</html>');
        $this->normalizer = new ResponseNormalizer(fn (): RecordingRenderer => $this->renderer);
    }

    public function testResponseIsReturnedUntouched(): void
    {
        $response = new JsonResponse(['ok' => true], Response::HTTP_CREATED);

        self::assertSame($response, $this->normalizer->normalize($response));
    }

    public function testResponseKeepsItsOwnStatusEvenWhenALegacyStatusIsGiven(): void
    {
        $response = new Response('body', Response::HTTP_CREATED);

        self::assertSame(
            Response::HTTP_CREATED,
            $this->normalizer->normalize($response, Response::HTTP_FORBIDDEN)->getStatusCode()
        );
    }

    public function testStringBecomesAResponseWithThatBody(): void
    {
        $response = $this->normalizer->normalize('<html>page</html>');

        self::assertSame('<html>page</html>', $response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNullBecomesAnEmptyResponse(): void
    {
        $response = $this->normalizer->normalize(null);

        self::assertSame('', $response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testLegacyStatusCodeIsAppliedToAWrappedString(): void
    {
        $response = $this->normalizer->normalize('', Response::HTTP_NOT_FOUND);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Pins the header contract of a wrapped legacy body: no Cache-Control (PHP already sent one
     * for the session) and no Content-Type at all, so the header() the controller sent survives.
     * Content-Type would only appear if someone called Response::prepare() here — this test is
     * what catches that.
     */
    public function testWrappedLegacyBodyCarriesNoContentTypeAndNoCacheControl(): void
    {
        $response = $this->normalizer->normalize('{"ok":true}');

        self::assertFalse($response->headers->has('Content-Type'));
        self::assertFalse($response->headers->has('Cache-Control'));
        self::assertSame(['date'], array_keys($response->headers->all()));
    }

    public function testAViewResponseIsRendered(): void
    {
        $response = $this->normalizer->normalize(
            new ViewResponse('@homepage/public/index.twig', ['title' => 'Home'])
        );

        self::assertSame([['@homepage/public/index.twig', ['title' => 'Home']]], $this->renderer->calls);
        self::assertSame('<html>rendered</html>', $response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAViewResponseKeepsItsStatusAndDeclaresHtml(): void
    {
        $response = $this->normalizer->normalize(
            new ViewResponse('@theme/pages/errors/404.twig', status: Response::HTTP_NOT_FOUND)
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertFalse($response->headers->has('Cache-Control'));
    }

    /**
     * Nothing is rendered until a controller actually returns a view: assembling the template
     * environment on every request would undo the point of the closure.
     */
    public function testTheRendererIsNotBuiltForALegacyResult(): void
    {
        $normalizer = new ResponseNormalizer(
            static fn () => self::fail('The renderer must not be built for a legacy result.')
        );

        self::assertSame('body', $normalizer->normalize('body')->getContent());
    }

    #[DataProvider('unsupportedResults')]
    public function testAnythingElseIsRejected(mixed $result, string $expectedType): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage($expectedType . ' given');

        $this->normalizer->normalize($result);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function unsupportedResults(): array
    {
        return [
            'array'  => [['not' => 'allowed'], 'array'],
            'int'    => [42, 'int'],
            'bool'   => [true, 'bool'],
            'object' => [new stdClass(), 'stdClass'],
        ];
    }
}
