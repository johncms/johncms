<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;

final class ForumLegacyRedirectResolver
{
    public function __construct(
        private CurrentUser $currentUser,
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
    ) {
    }

    public function resolve(array $query): ?string
    {
        checkRedirect();

        $act = isset($query['act']) ? trim((string) $query['act']) : '';

        if ($act === 'files') {
            $params = [];

            $c = isset($query['c']) ? abs((int) $query['c']) : 0;
            $s = isset($query['s']) ? abs((int) $query['s']) : 0;
            $t = isset($query['t']) ? abs((int) $query['t']) : 0;
            if ($c > 0) {
                $params['c'] = $c;
            } elseif ($s > 0) {
                $params['s'] = $s;
            } elseif ($t > 0) {
                $params['t'] = $t;
            }

            $do = isset($query['do']) ? (int) $query['do'] : 0;
            if ($do > 0 && $do < 10) {
                $params['do'] = $do;
            }

            if (array_key_exists('new', $query)) {
                $params['new'] = 1;
            }

            $page = isset($query['page']) ? abs((int) $query['page']) : 0;
            $start = isset($query['start']) ? abs((int) $query['start']) : 0;
            if ($page <= 1 && $start > 0) {
                $page = (int) floor($start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;
            }
            if ($page > 1) {
                $params['page'] = $page;
            }

            if ($params === []) {
                return '/forum/files/';
            }

            return '/forum/files/?' . http_build_query($params);
        }

        if ($act === 'file') {
            $id = isset($query['id']) ? abs((int) $query['id']) : 0;

            if ($id > 0) {
                return '/forum/download-file/' . $id . '/';
            }

            return '/forum/';
        }

        if ($act === 'show_post') {
            $id = isset($query['id']) ? abs((int) $query['id']) : 0;
            if ($id <= 0) {
                return '/forum/';
            }

            $url = '/forum/post/' . $id . '/';

            $page = isset($query['page']) ? abs((int) $query['page']) : 0;
            $start = isset($query['start']) ? abs((int) $query['start']) : 0;
            if ($page <= 1 && $start > 0) {
                $page = (int) floor($start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;
            }
            if ($page > 1) {
                $url .= '?page=' . $page;
            }

            return $url;
        }

        if ($act === 'new') {
            $do = isset($query['do']) ? trim((string) $query['do']) : '';

            if ($do === 'period') {
                $params = [];
                $vr = isset($query['vr']) ? abs((int) $query['vr']) : 0;
                if ($vr > 0) {
                    $params['vr'] = $vr;
                }

                $page = isset($query['page']) ? abs((int) $query['page']) : 0;
                $start = isset($query['start']) ? abs((int) $query['start']) : 0;
                if ($page <= 1 && $start > 0) {
                    $page = (int) floor($start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;
                }
                if ($page > 1) {
                    $params['page'] = $page;
                }

                return '/forum/topics-period/' . ($params === [] ? '' : '?' . http_build_query($params));
            }

            if ($do === 'reset') {
                return $this->currentUser->isValid() ? '/forum/unread/' : '/forum/latest-topics/';
            }

            if ($this->currentUser->isValid()) {
                $page = isset($query['page']) ? abs((int) $query['page']) : 0;
                $start = isset($query['start']) ? abs((int) $query['start']) : 0;
                if ($page <= 1 && $start > 0) {
                    $page = (int) floor($start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;
                }
                if ($page > 1) {
                    return '/forum/unread/?' . http_build_query(['page' => $page]);
                }

                return '/forum/unread/';
            }

            return '/forum/latest-topics/';
        }

        $legacyType = isset($query['type']) ? trim((string) $query['type']) : '';
        $legacyId = isset($query['id']) ? abs((int) $query['id']) : 0;

        if ($legacyId > 0 && ($legacyType === '' || $legacyType === 'section' || $legacyType === 'topics')) {
            $section = $this->sectionRepository->findById($legacyId);
            if ($section === null) {
                return '/forum/';
            }

            $url = $this->sectionPathService->getSectionUrl($section);
            $page = isset($query['page']) ? abs((int) $query['page']) : 0;
            if ($legacyType === 'topics' && $page > 1) {
                $url .= '?' . http_build_query(['page' => $page]);
            }

            return $url;
        }

        if ($legacyType === 'topic' && $legacyId > 0) {
            $page = isset($query['page']) ? abs((int) $query['page']) : 0;
            $start = isset($query['start']) ? abs((int) $query['start']) : 0;
            if ($page <= 1 && $start > 0) {
                $page = (int) floor($start / max(1, (int) $this->currentUser->user()->config->kmess)) + 1;
            }

            $url = $this->topicPathService->getTopicUrlById($legacyId, $page > 1 ? $page : null);
            if ($url === null) {
                return '/forum/';
            }

            $params = [];
            if (array_key_exists('clip', $query)) {
                $params['clip'] = 1;
            }
            if (array_key_exists('vote_result', $query)) {
                $params['vote_result'] = 1;
            }

            if ($params !== []) {
                $url .= '?' . http_build_query($params);
            }

            return $url;
        }

        if ($legacyId > 0 && $legacyType !== '' && $legacyType !== 'topic') {
            return '/forum/';
        }

        return null;
    }
}
