<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\ForumSearchQueryDTO;
use Johncms\Modules\Forum\Application\DTO\ForumSearchResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumErrorCode;
use Johncms\Modules\Forum\Application\Exceptions\ForumValidationException;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumSearchHistoryRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSearchRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Twig\Markup;

final readonly class ViewForumSearchUseCase
{
    public function __construct(
        private ForumSearchRepositoryInterface $searchRepository,
        private ForumSearchHistoryRepositoryInterface $searchHistoryRepository,
        private ForumTopicPathService $topicPathService,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
    ) {
    }

    public function execute(ForumSearchQueryDTO $query): ForumSearchResultDTO
    {
        $search = preg_replace('/[^\w\x7F-\xFF\s]/', ' ', trim($query->search)) ?? '';
        $search = trim($search);

        if ($search !== '' && (mb_strlen($search) < 4 || mb_strlen($search) > 64)) {
            throw new ForumValidationException(ForumErrorCode::FORUM_SEARCH_INVALID_LENGTH, 'Invalid search query length.');
        }

        $includeDeleted = $this->currentUser->rights >= 7;
        $limit = (int) $this->currentUser->config->kmess;
        $total = 0;
        $rows = [];

        if ($search !== '') {
            if ($query->searchInTopicNames) {
                $total = $this->searchRepository->countTopicsByName($search, $includeDeleted);
                if ($total > 0) {
                    $rows = $this->searchRepository->getTopicsByName($search, $includeDeleted, $query->start, $limit);
                }
            } else {
                $total = $this->searchRepository->countMessagesByText($search, $includeDeleted);
                if ($total > 0) {
                    $rows = $this->searchRepository->getMessagesByText($search, $includeDeleted, $query->start, $limit);
                }
            }
        }

        $results = $this->mapResults($search, $query->searchInTopicNames, $rows, $includeDeleted);
        $historyTerms = $this->buildHistory($search, $total > 0);

        return new ForumSearchResultDTO(
            query: $search,
            searchInTopicNames: $query->searchInTopicNames,
            total: $total,
            results: $results,
            historyTerms: $historyTerms,
            start: max(0, $query->start),
        );
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function mapResults(string $search, bool $searchInTopicNames, array $rows, bool $includeDeleted): array
    {
        $results = [];
        $searchParts = array_values(array_filter(explode(' ', $search), static fn(string $value): bool => $value !== ''));

        foreach ($rows as $row) {
            if ($searchInTopicNames) {
                $safeTopicName = htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                $row['name'] = new Markup($this->highlightMany($safeTopicName, $searchParts), 'UTF-8');
                $date = $includeDeleted ? (int) ($row['mod_last_post_date'] ?? 0) : (int) ($row['last_post_date'] ?? 0);
                $row['post_url'] = '';
                $row['read_more'] = '';
                $row['formatted_text'] = null;
                $row['topic_url'] = $this->topicPathService->getTopicUrlById((int) ($row['id'] ?? 0)) ?? '/forum/';
            } else {
                $messageText = (string) ($row['text'] ?? '');
                $plainMessageText = $this->extractPlainText($messageText);
                $date = (int) ($row['date'] ?? 0);
                $row['name'] = new Markup(htmlspecialchars((string) ($row['topic_name'] ?? ''), ENT_QUOTES, 'UTF-8'), 'UTF-8');
                $row['formatted_text'] = new Markup($this->buildMessagePreview($plainMessageText, $searchParts), 'UTF-8');
                $row['read_more'] = mb_strlen($plainMessageText) > 500 ? '/forum/post/' . (int) ($row['id'] ?? 0) . '/' : '';
                $row['topic_url'] = $this->topicPathService->getTopicUrlById((int) ($row['topic_id'] ?? 0)) ?? '/forum/';
                $row['post_url'] = '/forum/post/' . (int) ($row['id'] ?? 0) . '/';
            }

            $row['formatted_date'] = $this->dateFormatter->format($date);
            $results[] = $row;
        }

        return $results;
    }

    /**
     * @param string[] $searchParts
     */
    private function buildMessagePreview(string $text, array $searchParts): string
    {
        $position = 100;
        foreach ($searchParts as $searchPart) {
            $needle = strtolower(str_replace('*', '', $searchPart));
            $foundPos = $needle !== '' ? mb_stripos($text, $needle) : false;
            if ($foundPos !== false) {
                $position = $foundPos < 100 ? 100 : $foundPos;
                break;
            }
        }

        $prepared = mb_substr($text, $position - 100, 400);
        $prepared = htmlspecialchars($prepared, ENT_QUOTES, 'UTF-8');

        return $this->highlightMany($prepared, $searchParts);
    }

    private function extractPlainText(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?\s*>/iu', ' ', $html) ?? $html;
        $text = preg_replace('/<\s*\/?\s*(p|div|li|ul|ol|blockquote)\b[^>]*>/iu', ' ', $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param string[] $searchParts
     */
    private function highlightMany(string $text, array $searchParts): string
    {
        $highlighted = $text;

        foreach ($searchParts as $searchPart) {
            $keyword = str_replace('*', '', $searchPart);
            if (mb_strlen($keyword) < 3) {
                continue;
            }

            $pattern = '|' . preg_quote($keyword, '/') . '|siu';
            $highlighted = preg_replace(
                $pattern,
                '<span style="background-color: rgba(255, 193, 7, 0.28); border-radius: 2px;">$0</span>',
                $highlighted
            ) ?? $highlighted;
        }

        return $highlighted;
    }

    /**
     * @return string[]
     */
    private function buildHistory(string $search, bool $addToHistory): array
    {
        if (! $this->currentUser->isValid()) {
            return [];
        }

        $history = $this->searchHistoryRepository->getByUserId((int) $this->currentUser->id);
        if ($addToHistory && $search !== '' && ! in_array($search, $history, true)) {
            if (count($history) > 20) {
                array_shift($history);
            }

            $history[] = $search;
            $this->searchHistoryRepository->saveForUser((int) $this->currentUser->id, $history);
        }

        sort($history);

        return $history;
    }
}
