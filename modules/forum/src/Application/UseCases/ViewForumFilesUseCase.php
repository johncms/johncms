<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\ForumFilesQueryDTO;
use Johncms\Modules\Forum\Application\DTO\ForumFilesViewResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumFilesContextNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Query\ForumFileCountQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileListingQuery;
use Johncms\Modules\Forum\Domain\Query\ForumFileScopeQuery;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class ViewForumFilesUseCase
{
    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumTopicPathService $topicPathService,
        private Tools $tools,
        private User $currentUser,
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
                throw new ForumFilesContextNotFoundException('Category context not found.');
            }

            return [
                'caption'     => __('Category Files'),
                'contextName' => $section->name,
                'contextUrl'  => $section->url,
                'categoryId'  => $section->id,
                'sectionId'   => null,
                'topicId'     => null,
                'contextId'   => $section->id,
            ];
        }

        if ($query->contextSectionId > 0) {
            $section = $this->sectionRepository->findById($query->contextSectionId);
            if ($section === null) {
                throw new ForumFilesContextNotFoundException('Section context not found.');
            }

            return [
                'caption'     => __('Section files'),
                'contextName' => $section->name,
                'contextUrl'  => $section->url,
                'categoryId'  => null,
                'sectionId'   => $section->id,
                'topicId'     => null,
                'contextId'   => $section->id,
            ];
        }

        if ($query->contextTopicId > 0) {
            $topic = $this->topicRepository->findById($query->contextTopicId);
            if ($topic === null) {
                throw new ForumFilesContextNotFoundException('Topic context not found.');
            }

            return [
                'caption'     => __('Topic Files'),
                'contextName' => $topic->name,
                'contextUrl'  => $topic->url,
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
        $files = [];

        if ($total > 0) {
            $forumSettings = $this->getForumSettings();
            $listingScope = $this->createScopeQuery($context, $this->currentUser->rights >= 7);
            $listingFilter = $query->isNew
                ? ForumFileCountQuery::forNew($listingScope, $newFrom)
                : ForumFileCountQuery::forType($listingScope, $query->fileType);

            $rows = $this->fileRepository->getListingItems(new ForumFileListingQuery(
                filter: $listingFilter,
                upfp: ! empty($forumSettings['upfp']),
                start: $query->start,
                limit: (int) $this->currentUser->config->kmess,
            ));

            $files = $this->mapRowsToFiles($rows);
        }

        return new ForumFilesViewResultDTO(
            caption: $caption,
            contextName: $context['contextName'],
            contextUrl: $context['contextUrl'],
            template: 'forum::files_list',
            viewData: [
                'title'         => $caption,
                'page_title'    => $caption,
                'pagination'    => $this->tools->displayPagination('/forum/files/?' . ($query->isNew ? 'new' : 'do=' . $query->fileType) . $lnk . '&amp;', $query->start, $total, $this->currentUser->config->kmess),
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
     *   categoryId: ?int,
     *   sectionId: ?int,
     *   topicId: ?int,
     *   contextId: int
     * } $context
     */
    private function buildSectionsResult(array $context, string $lnk, int $newFrom): ForumFilesViewResultDTO
    {
        $types = $this->getFileTypes();
        $scope = $this->createScopeQuery($context, $this->currentUser->rights >= 7);

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
            template: 'forum::files_sections',
            viewData: [
                'title'      => $context['caption'],
                'page_title' => $context['caption'],
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
        $userRightsNames = $this->getUserRightsNames();

        foreach ($rows as $row) {
            $text = mb_substr((string) ($row['text'] ?? ''), 0, 500);
            $text = $this->tools->checkout($text, 1, 0);
            $text = preg_replace('/\[\/?(\w+).*?\]/is', '', $text) ?: '';

            $page = (int) ceil((int) ($row['page'] ?? 0) / $this->currentUser->config->kmess);
            $row['post_text'] = $text;
            $row['post_time'] = $this->tools->displayDate((int) $row['time']);
            $row['user_profile_link'] = '';

            if ($this->currentUser->isValid() && (int) $this->currentUser->id !== (int) $row['user_id']) {
                $row['user_profile_link'] = '/profile/?user=' . $row['user_id'];
            }

            $row['user_rights_name'] = $userRightsNames[(int) $row['rights']] ?? '';
            $row['user_name'] = $row['name'];
            $row['post_url'] = '/forum/post/' . $row['post'] . '/';
            $row['topic_url'] = $this->topicPathService->getTopicUrlById((int) $row['topic'], $page > 1 ? $page : null) ?? '/forum/';

            $filePath = UPLOAD_PATH . 'forum/attach/' . $row['filename'];
            $fileSize = is_file($filePath) ? @filesize($filePath) : 0;
            $row['file_size'] = round($fileSize / 1024, 0);
            $attExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $row['file_preview'] = '';
            $row['file_url'] = '/forum/download-file/' . $row['id'] . '/';
            if (in_array($attExt, ['gif', 'jpg', 'jpeg', 'png'], true)) {
                $row['file_preview'] = '/assets/modules/forum/thumbinal.php?file=' . urlencode((string) $row['filename']);
            }

            $files[] = $row;
        }

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function getUserRightsNames(): array
    {
        return [
            3 => __('Forum moderator'),
            4 => __('Download moderator'),
            5 => __('Library moderator'),
            6 => __('Super moderator'),
            7 => __('Administrator'),
            9 => __('Supervisor'),
        ];
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
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $setForum = (array) $this->currentUser->set_forum;
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
}
