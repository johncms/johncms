<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Users\User;

final class ForumLegacyRedirectResolver
{
    public function __construct(
        private User $currentUser,
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumSectionPathService $sectionPathService,
    ) {
    }

    public function resolve(array $query): ?string
    {
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

            $start = isset($query['start']) ? abs((int) $query['start']) : 0;
            if ($start > 0) {
                $params['start'] = $start;
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

            $start = isset($query['start']) ? abs((int) $query['start']) : 0;
            if ($start > 0) {
                $url .= '?start=' . $start;
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

                $start = isset($query['start']) ? abs((int) $query['start']) : 0;
                if ($start > 0) {
                    $params['start'] = $start;
                }

                return '/forum/topics-period/' . ($params === [] ? '' : '?' . http_build_query($params));
            }

            if ($do === 'reset') {
                return $this->currentUser->isValid() ? '/forum/unread/' : '/forum/latest-topics/';
            }

            if ($this->currentUser->isValid()) {
                $start = isset($query['start']) ? abs((int) $query['start']) : 0;
                if ($start > 0) {
                    return '/forum/unread/?' . http_build_query(['start' => $start]);
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

        if ($legacyId > 0 && $legacyType !== '' && $legacyType !== 'topic') {
            return '/forum/';
        }

        return null;
    }
}
