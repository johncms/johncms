<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\DTO\ForumFilesViewResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Query\ForumFileCountQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileListingQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileScopeQuery;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Utils\DateFormatterInterface;
use Simba77\EmbedMedia\Embed;
use Twig\Markup;
use Johncms\Modules\Forum\Infrastructure\Storage\ForumAttachmentStorage;

final readonly class ViewForumFilesUseCase
{
    public function __construct(
        private StaffTitles $staffTitles,
        private ForumFileRepositoryInterface $fileRepository,
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private DateFormatterInterface $dateFormatter,
        private SmiliesRendererInterface $smiliesRenderer,
        private HtmlSanitizerInterface $sanitizer,
        private Embed $embed,
        private CurrentUser $currentUser,
        private AccessCheckerInterface $accessChecker,
        private ForumAttachmentStorage $attachments,
    ) {
    }

    public function execute(ForumFilesQueryDTO $query): ForumFilesViewResultDTO
    {
        $newFrom = time() - 86400;
        $context = $this->resolveContext($query);
        $lnk = $this->buildLinkSuffix($context['categoryId'], $context['sectionId'], $context['topicId']);

        if ($query->fileType > 0 || $query->isNew) {
            return $this->buildListResult($query, $context, $lnk, $newFrom);
        }

        return $this->buildSectionsResult($context, $lnk, $newFrom);
    }

    /**
     * @return array{
     *   caption: string,
     *   contextName: ?string,
     *   contextUrl: ?string,
     *   contextType: string,
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * }
     */
    private function resolveContext(ForumFilesQueryDTO $query): array
    {
        if ($query->contextCategoryId > 0) {
            $section = $this->sectionRepository->findById($query->contextCategoryId);
            if ($section === null) {
                throw new ForumNotFoundException('Category context not found.');
            }

            return [
                'caption'     => __('Category Files'),
                'contextName' => $section->name,
                'contextUrl'  => $section->url,
                'contextType' => 'category',
                'categoryId'  => $section->id,
                'sectionId'   => null,
                'topicId'     => null,
                'contextId'   => $section->id,
            ];
        }

        if ($query->contextSectionId > 0) {
            $section = $this->sectionRepository->findById($query->contextSectionId);
            if ($section === null) {
                throw new ForumNotFoundException('Section context not found.');
            }

            return [
                'caption'     => __('Section files'),
                'contextName' => $section->name,
                'contextUrl'  => $section->url,
                'contextType' => 'section',
                'categoryId'  => null,
                'sectionId'   => $section->id,
                'topicId'     => null,
                'contextId'   => $section->id,
            ];
        }

        if ($query->contextTopicId > 0) {
            $topic = $this->topicRepository->findById($query->contextTopicId);
            if ($topic === null) {
                throw new ForumNotFoundException('Topic context not found.');
            }

            return [
                'caption'     => __('Topic Files'),
                'contextName' => $topic->name,
                'contextUrl'  => $topic->url,
                'contextType' => 'topic',
                'categoryId'  => null,
                'sectionId'   => null,
                'topicId'     => $topic->id,
                'contextId'   => $topic->id,
            ];
        }

        return [
            'caption'     => __('Forum Files'),
            'contextName' => null,
            'contextUrl'  => null,
            'contextType' => 'forum',
            'categoryId'  => null,
            'sectionId'   => null,
            'topicId'     => null,
            'contextId'   => 0,
        ];
    }

    /**
     * @param array{
     *   caption: string,
     *   contextName: ?string,
     *   contextUrl: ?string,
     *   contextType: string,
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * } $context
     */
    private function buildListResult(ForumFilesQueryDTO $query, array $context, string $lnk, int $newFrom): ForumFilesViewResultDTO
    {
        $scope = $this->createScopeQuery($context, false);
        $countQuery = $query->isNew
            ? ForumFileCountQuery::forNew($scope, $newFrom)
            : ForumFileCountQuery::forType($scope, $query->fileType);
        $total = $this->fileRepository->countForListing($countQuery);

        $caption = $query->isNew ? __('New Files') : $context['caption'];
        $seoMeta = $this->buildSeoMeta($query, $context, $caption);
        $files = [];

        if ($total > 0) {
            $forumSettings = $this->getForumSettings();
            $listingScope = $this->createScopeQuery($context, $this->accessChecker->allows(ForumPermissions::DELETED_VIEW));
            $listingFilter = $query->isNew
                ? ForumFileCountQuery::forNew($listingScope, $newFrom)
                : ForumFileCountQuery::forType($listingScope, $query->fileType);

            $rows = $this->fileRepository->getListingItems(new ForumFileListingQuery(
                filter: $listingFilter,
                upfp: ! empty($forumSettings['upfp']),
                start: $query->start,
                limit: (int) $this->currentUser->user()->config->kmess,
            ));

            $files = $this->mapRowsToFiles($rows);
        }

        return new ForumFilesViewResultDTO(
            caption: $caption,
            contextName: $context['contextName'],
            contextUrl: $context['contextUrl'],
            template: '@forum/public/files-list.twig',
            viewData: [
                'title'         => $seoMeta['title'],
                'page_title'    => $seoMeta['page_title'],
                'description'   => $seoMeta['description'],
                'canonical'     => $seoMeta['canonical'],
                'back_url'      => '/forum/files/' . ($lnk !== '' ? '?' . str_replace('&amp;', '', $lnk) : ''),
                'back_url_name' => __('List of sections'),
                'files'         => $files,
                'total'         => $total,
                'new_url'       => '/forum/files/?new' . $lnk,
            ],
        );
    }

    /**
     * @param array{
     *   caption: string,
     *   contextName: ?string,
     *   contextUrl: ?string,
     *   contextType: string,
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * } $context
     */
    private function buildSectionsResult(array $context, string $lnk, int $newFrom): ForumFilesViewResultDTO
    {
        $types = $this->getFileTypes();
        $scope = $this->createScopeQuery($context, $this->accessChecker->allows(ForumPermissions::DELETED_VIEW));
        $seoMeta = $this->buildSeoMeta(
            new ForumFilesQueryDTO(start: 0, contextCategoryId: $context['categoryId'] ?? 0, contextSectionId: $context['sectionId'] ?? 0, contextTopicId: $context['topicId'] ?? 0, fileType: 0, isNew: false),
            $context,
            $context['caption']
        );

        $countNew = $this->fileRepository->countByQuery(ForumFileCountQuery::forNew($scope, $newFrom));

        $total = 0;
        $sections = [];
        foreach ($types as $key => $type) {
            $count = $this->fileRepository->countByQuery(ForumFileCountQuery::forType($scope, $key));

            if ($count > 0) {
                $sections[] = [
                    'url'   => '/forum/files/?do=' . $key . $lnk,
                    'name'  => $type,
                    'count' => $count,
                ];
            }

            $total += $count;
        }

        return new ForumFilesViewResultDTO(
            caption: $context['caption'],
            contextName: $context['contextName'],
            contextUrl: $context['contextUrl'],
            template: '@forum/public/files-sections.twig',
            viewData: [
                'title'       => $seoMeta['title'],
                'page_title'  => $seoMeta['page_title'],
                'description' => $seoMeta['description'],
                'canonical'   => $seoMeta['canonical'],
                'back_url'   => $context['contextUrl'] ?? '/forum/',
                'sections'   => $sections,
                'total'      => $total,
                'new_url'    => '/forum/files/?new' . $lnk,
                'new_count'  => $countNew,
            ],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapRowsToFiles(array $rows): array
    {
        $files = [];
        $this->staffTitles->preload(array_map(static fn (array $row): int => (int) ($row['user_id'] ?? 0), $rows));

        foreach ($rows as $row) {
            $text = mb_substr((string) ($row['text'] ?? ''), 0, 500);
            $text = $this->sanitizer->sanitize($text);
            $text = $this->embed->embedMedia($text);
            $text = $this->smiliesRenderer->render($text, $this->staffTitles->isStaff((int) $row['user_id']));

            $page = (int) ceil((int) ($row['page'] ?? 0) / $this->currentUser->user()->config->kmess);
            $row['post_text'] = new Markup($text, 'UTF-8');
            $row['post_time'] = $this->dateFormatter->format((int) $row['time']);
            $row['user_is_online'] = time() <= (int) ($row['lastdate'] ?? 0) + 300;
            $row['user_profile_link'] = '';

            if ($this->currentUser->isValid() && $this->currentUser->id() !== (int) $row['user_id']) {
                $row['user_profile_link'] = '/profile/' . $row['user_id'];
            }

            $row['user_rights_name'] = $this->staffTitles->titleFor((int) $row['user_id']);
            $row['user_name'] = $row['name'];
            $row['post_url'] = '/forum/post/' . $row['post'] . '/';
            $row['topic_url'] = $this->topicPathService->getTopicUrlById((int) $row['topic'], $page > 1 ? $page : null) ?? '/forum/';

            $filename = (string) $row['filename'];
            $row['file_size'] = round($this->attachments->size($filename) / 1024, 0);
            $row['file_preview'] = '';
            $row['file_url'] = '/forum/download-file/' . $row['id'] . '/';
            if ($this->attachments->isImage($filename)) {
                $row['file_preview'] = '/forum/file-preview/' . $row['id'];
            }

            $files[] = $row;
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function getFileTypes(): array
    {
        return [
            1 => __('Windows applications'),
            2 => __('Java applications'),
            3 => __('SIS'),
            4 => __('txt'),
            5 => __('Pictures'),
            6 => __('Archive'),
            7 => __('Videos'),
            8 => __('MP3'),
            9 => __('Other'),
        ];
    }

    private function getForumSettings(): array
    {
        $setForumDefault = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $setForum = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->user()->set_forum)) {
            $setForum = (array) $this->currentUser->user()->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }

    private function buildLinkSuffix(?int $categoryId, ?int $sectionId, ?int $topicId): string
    {
        if ($categoryId !== null) {
            return '&amp;c=' . $categoryId;
        }

        if ($sectionId !== null) {
            return '&amp;s=' . $sectionId;
        }

        if ($topicId !== null) {
            return '&amp;t=' . $topicId;
        }

        return '';
    }

    /**
     * @param array{
     *   caption: string,
     *   contextName: ?string,
     *   contextUrl: ?string,
     *   contextType: string,
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * } $context
     */
    private function createScopeQuery(array $context, bool $includeDeleted): ForumFileScopeQuery
    {
        return new ForumFileScopeQuery(
            categoryId: $context['categoryId'],
            sectionId: $context['sectionId'],
            topicId: $context['topicId'],
            includeDeleted: $includeDeleted,
        );
    }

    /**
     * @param array{
     *   caption: string,
     *   contextName: ?string,
     *   contextUrl: ?string,
     *   contextType: string,
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * } $context
     * @return array{title: string, page_title: string, description: string, canonical: string}
     */
    private function buildSeoMeta(ForumFilesQueryDTO $query, array $context, string $caption): array
    {
        $contextName = (string) ($context['contextName'] ?? '');
        $page = (int) floor($query->start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;

        $baseTitle = $caption;
        if ($contextName !== '') {
            $baseTitle .= ': ' . $contextName;
        }

        if ($query->fileType > 0) {
            $fileTypeName = $this->getFileTypes()[$query->fileType] ?? '';
            if ($fileTypeName !== '') {
                $baseTitle .= ' - ' . $fileTypeName;
            }
        }

        $title = $baseTitle;
        if ($page > 1) {
            $title .= ' - ' . d__('system', 'Page') . ' ' . $page;
        }

        $description = $title;

        $canonicalParams = [];
        if ($query->isNew) {
            $canonicalParams['new'] = 1;
        } elseif ($query->fileType > 0) {
            $canonicalParams['do'] = $query->fileType;
        }
        if ($query->contextCategoryId > 0) {
            $canonicalParams['c'] = $query->contextCategoryId;
        } elseif ($query->contextSectionId > 0) {
            $canonicalParams['s'] = $query->contextSectionId;
        } elseif ($query->contextTopicId > 0) {
            $canonicalParams['t'] = $query->contextTopicId;
        }
        if ($page > 1) {
            $canonicalParams['page'] = $page;
        }

        $canonical = config('johncms.homeurl') . '/forum/files/';
        if ($canonicalParams !== []) {
            $canonical .= '?' . http_build_query($canonicalParams);
        }

        return [
            'title'       => $title,
            'page_title'  => $baseTitle,
            'description' => $description,
            'canonical'   => $canonical,
        ];
    }
}
