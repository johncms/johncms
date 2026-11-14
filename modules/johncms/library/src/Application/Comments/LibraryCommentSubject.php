<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Comments;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjectInterface;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;

/**
 * An article of the library, discussed through the comments of the core.
 *
 * An article awaiting moderation is discussed by the moderators only. The one who uploaded it
 * removes the comments under it; the ban of the library closes its discussions too. An article
 * whose comments were switched off is still read with what it has, and takes no new ones.
 */
final readonly class LibraryCommentSubject implements CommentSubjectInterface
{
    public const string TYPE = 'library';

    /** The full ban, the ban of the comments and the ban of the library. */
    private const array BANS = [1, 10, 15];

    public function __construct(
        private AccessCheckerInterface $access,
        private LibraryTextRepositoryInterface $articles,
        private LibraryArticlePathService $articlePaths,
        private LibraryCategoryPathService $categoryPaths,
    ) {
    }

    public function type(): string
    {
        return self::TYPE;
    }

    public function find(int $id): ?CommentSubjectDTO
    {
        $article = $this->articles->findById($id);
        if ($article === null || (! $article->getAttribute('premod') && ! $this->access->allows(LibraryPermissions::MODERATE))) {
            return null;
        }

        $url = $this->articlePaths->getArticleUrl($article);
        $ownerId = (int) $article->getAttribute('uploader_id');
        $name = (string) $article->getAttribute('name');

        return new CommentSubjectDTO(
            type: self::TYPE,
            id: $id,
            title: $name,
            url: $url,
            discussionUrl: '/library/article/' . $id . '/comments',
            ownerId: $ownerId > 0 ? $ownerId : null,
            open: (bool) $article->getAttribute('comments'),
            ownerCanDelete: true,
            bans: self::BANS,
            breadcrumbs: [
                ['name' => d__('library', 'Library'), 'url' => '/library/'],
                ...$this->sections((int) $article->getAttribute('cat_id')),
                ['name' => $name, 'url' => $url],
            ],
        );
    }

    public function commentsChanged(int $id, int $delta): void
    {
        $this->articles->changeCommentCount($id, $delta);
    }

    /**
     * @return list<array{name: string, url: string}>
     */
    private function sections(int $sectionId): array
    {
        if ($sectionId < 1) {
            return [];
        }

        $sections = [];
        foreach ((new Tree($sectionId))->processNavPanel()->result() as $section) {
            $sections[] = [
                'name' => (string) $section['name'],
                'url'  => $this->categoryPaths->getCategoryUrlById((int) $section['id']) ?? '/library/',
            ];
        }

        return $sections;
    }
}
