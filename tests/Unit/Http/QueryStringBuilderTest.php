<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\QueryStringBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Tests for QueryStringBuilder (plan stage 1a): the query-string assembly extracted from the
 * former Request::getQueryString().
 */
final class QueryStringBuilderTest extends TestCase
{
    private QueryStringBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new QueryStringBuilder();
    }

    public function testPathOnlyWhenNoParameters(): void
    {
        self::assertSame('/forum/', $this->builder->build('/forum/', []));
    }

    public function testKeepsExistingParameters(): void
    {
        self::assertSame('/forum/?id=5', $this->builder->build('/forum/', ['id' => 5]));
    }

    public function testRemovesRequestedParameters(): void
    {
        self::assertSame(
            '/forum/?id=5',
            $this->builder->build('/forum/', ['id' => 5, 'page' => 3], ['page']),
        );
    }

    public function testRemovingTheOnlyParameterLeavesPathOnly(): void
    {
        self::assertSame('/forum/', $this->builder->build('/forum/', ['page' => 3], ['page']));
    }

    public function testAddedParametersOverrideExistingOnes(): void
    {
        self::assertSame(
            '/forum/?page=7',
            $this->builder->build('/forum/', ['page' => 3], [], ['page' => 7]),
        );
    }

    public function testRemoveTakesPrecedenceOverKeepButAddCanReintroduce(): void
    {
        // page is removed from the current set, then re-added with a new value.
        self::assertSame(
            '/forum/?id=5&page=2',
            $this->builder->build('/forum/', ['id' => 5, 'page' => 9], ['page'], ['page' => 2]),
        );
    }
}
