<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News;

use Johncms\Modules\Admin\Domain\Enums\SortDirection;
use Johncms\Modules\News\Application\Services\NewsListFilterMapper;
use Johncms\Modules\News\Domain\Enums\ArticleStatus;
use Johncms\Modules\News\Domain\Enums\NewsListSort;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use PHPUnit\Framework\TestCase;

final class NewsListFilterMapperTest extends TestCase
{
    public function testTheDefaultsLeaveNothingInTheAddress(): void
    {
        $mapper = new NewsListFilterMapper();

        self::assertSame('/admin/news', $mapper->url('/admin/news', $mapper->fromQuery([])));
    }

    public function testEveryValueGoesBackIntoTheAddressItCameFrom(): void
    {
        $mapper = new NewsListFilterMapper();
        $query = ['q' => 'release', 'section' => '0', 'status' => 'draft', 'author' => '7', 'sort' => 'views', 'dir' => 'asc'];

        $filter = $mapper->fromQuery($query);

        self::assertSame(0, $filter->section);
        self::assertSame(ArticleStatus::DRAFT, $filter->status);
        self::assertSame(7, $filter->author);
        self::assertSame(NewsListSort::VIEWS, $filter->sort);
        self::assertSame(SortDirection::ASC, $filter->direction);
        self::assertSame($query, $mapper->toQuery($filter));
    }

    public function testTheSectionsTakeNeitherTheFiltersNorTheColumnsOfTheArticles(): void
    {
        $filter = (new NewsListFilterMapper())->fromQuery(['tab' => 'sections', 'status' => 'draft', 'author' => '7', 'sort' => 'views']);

        self::assertSame(NewsListTab::SECTIONS, $filter->tab);
        self::assertNull($filter->status);
        self::assertNull($filter->author);
        self::assertSame(NewsListSort::NAME, $filter->sort);
        self::assertSame(SortDirection::ASC, $filter->direction);
    }

    public function testJunkReadsAsTheDefault(): void
    {
        $filter = (new NewsListFilterMapper())->fromQuery(['tab' => 'x', 'section' => '-1', 'status' => 'x', 'author' => ['7'], 'sort' => 'x', 'dir' => 'x']);

        self::assertSame(NewsListTab::ARTICLES, $filter->tab);
        self::assertNull($filter->section);
        self::assertNull($filter->status);
        self::assertNull($filter->author);
        self::assertSame(NewsListSort::ID, $filter->sort);
        self::assertSame(SortDirection::DESC, $filter->direction);
    }
}
