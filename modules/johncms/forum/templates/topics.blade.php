<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

/**
 * @var $sections
 * @var $online
 * @var $files_count
 * @var $unread_count
 * @var $create_access
 * @var $pagination
 * @var array $topics
 */
?>
@extends('system::layout/default')
@section('content')
    @include('johncms/forum::header',
    [
        'unread_count' => $unread_count,
        'files_count'  => $files_count,
        'files_url'    => '/forum/?act=files&s=' . $id,
        'files_name'   => __('Category Files'),
    ])

    @if ($total === 0)
        @include('system::app/alert',
            [
                'alert_type' => 'alert-info',
                'alert'      => __('No topics in this section'),
            ])
    @endif
    @if ($create_access)
        <div class="mb-3">
            <a href="{{$createTopicUrl}}" class="btn btn-primary"><?= __('New Topic') ?></a>
        </div>
    @endif

    @foreach ($topics as $topic)
        <div class="forum-section">
            <div class="section-header">
                <div class="d-flex align-items-center w-100">

                    <div class="topic-icons d-flex align-items-center">
                        @if($topic['has_icons'])
                            @if ($topic['pinned'])
                                <div class="me-1" title="<?= __('Pinned topic') ?>">
                                    <svg class="icon">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#pin"/>
                                    </svg>
                                </div>
                            @endif
                            @if ($topic['has_poll'])
                                <div class="me-1" title="<?= __('Topic has poll') ?>">
                                    <svg class="icon">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#bar-chart"/>
                                    </svg>
                                </div>
                            @endif
                            @if ($topic['closed'])
                                <div class="me-1" title="<?= __('Closed topic') ?>">
                                    <svg class="icon">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#lock"/>
                                    </svg>
                                </div>
                            @endif
                            @if ($topic['deleted'])
                                <div class="me-1" title="<?= __('Deleted topic') ?>">
                                    <svg class="icon">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#x"/>
                                    </svg>
                                </div>
                            @endif
                        @endif
                    </div>

                    <a href="<?= $topic['url'] ?>"
                       class="section-name flex-grow-1 flex-md-grow-0"
                    ><?= $topic['name'] ?></a>

                    @if ($topic['deleted'])
                        <div class="ms-3">
                            <span class="badge bg-danger"><?= __('Deleted topic') ?></span>
                        </div>
                    @else
                        @if (! empty($topic['last_page_url']))
                            <a href="<?= $topic['last_page_url'] ?>" title="<?= __('Last page') ?>">
                                <span
                                    class="badge rounded-pill <?= $topic['unread'] ? 'bg-danger' : 'bg-light border' ?> ms-3"
                                ><?= $topic['post_count'] ?></span>
                            </a>
                        @else
                            <span
                                class="badge rounded-pill <?= $topic['unread'] ? 'bg-danger' : 'bg-light border' ?> ms-3"
                            ><?= $topic['post_count'] ?></span>
                        @endif
                    @endif
                </div>
            </div>
            <div class="small pt-2 text-muted">
                    <?= __('Author') ?> : {{ $topic['user_name'] }},
                    <?= __('Last post') ?>: {{ $topic['last_post_date'] }}, {{ $topic['last_post_author'] }}
            </div>
        </div>
    @endforeach
    <div class="pt-2">
        <div><?= __('Total') ?>: <?= $total ?></div>
        <!-- Page switching -->
        @if (! empty($pagination))
            <div class="mt-2"><?= $pagination ?></div>
        @endif
    </div>
    @include('johncms/forum::footer', [
        'online'   => $online,
        'who_url'  => '/forum/?act=who',
        'who_name' => __('Who in Forum'),
    ])
@endsection
