<?php
/**
 * @var string $locale
 * @var Johncms\Users\User $user
 * @var Johncms\Counters $counters
 * @var Johncms\Ads $ads
 *
 * @var Johncms\View\MetaTagManager $metaTags
 */

$counters = $container->get('counters');
$notifications = $counters->notifications();
$analytics = $counters->counters();
?><!DOCTYPE html>
<html lang="{{ $locale }}" dir="<?= $locale === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=2.0">
    <meta name="HandheldFriendly" content="true">
    <meta name="MobileOptimized" content="width">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="Generator" content="JohnCMS, https://johncms.com">
    <meta name="csrf-token" content="<?= $csrf_token ?>">
    @stack('meta')
    @if (! empty($metaTags->getKeywords()))
        <meta name="keywords" content="<?= $metaTags->getKeywords() ?>">
    @endif
    @if (! empty($metaTags->getDescription()))
        <meta name="description" content="<?= $metaTags->getDescription() ?>">
    @endif
    @if (! empty($metaTags->getCanonical()))
        <link rel="canonical" href="<?= $metaTags->getCanonical() ?>">
    @endif
    <meta name="theme-color" content="#586776">

    <?= viteAssets('themes/admin/src/js/app.ts') ?>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,400i,700,700i&display=swap">
    <link rel="shortcut icon" href="/favicon.ico">
    @stack('styles')
    <title>{{ $metaTags->getTitle() }}</title>
</head>
<body>
<div class="wrapper min-vh-100 d-flex flex-column justify-content-between" id="app">
    <div class="page_layout justify-content-end d-flex w-100">
        <div class="sidebar">
            <div class="sidebar__logo">
                <a href="/admin/">
                    <span class="logo__image"><img src="<?= asset('images/logo.svg') ?>" alt="logo" style="width: 70%;" class="img-fluid"></span>
                </a>
            </div>
            <div class="sidebar__wrapper d-flex flex-column">
                @include('system::app.sidebar-user-menu', ['notifications' => $notifications])
                @include('system::app/sidebar-main-menu')
            </div>
        </div>
        <div class="content-container content-container-padding d-flex flex-column">
            <nav class="navbar navbar-expand-lg top_nav fixed-top border-bottom shadow">
                <div class="container-fluid">
                    <div class="navbar-wrapper d-flex w-100 justify-content-between">
                        <div class="navbar-toggle">
                            <button type="button" class="navbar-toggler">
                                <span class="navbar-toggler-bar bar1"></span>
                                <span class="navbar-toggler-bar bar2"></span>
                                <span class="navbar-toggler-bar bar3"></span>
                            </button>
                        </div>
                        <div class="logo">
                            <a href="/">
                                <img src="<?= asset('images/logo_mobile.svg') ?>" alt="logo" class="img-fluid" style="height: 30px; margin-bottom: -18px;">
                            </a>
                        </div>
                        <div>
                            @if ($user)
                                <a href="/notifications/" class="icon_with_badge me-2">
                                    <svg class="icon icon_messages">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#messages"/>
                                    </svg>
                                    @if (! empty($notifications['all']))
                                        <span class="badge bg-danger rounded-pill"><?= $notifications['all'] ?></span>
                                    @endif
                                </a>
                            @else
                                <a href="/login/" class="icon_with_badge">
                                    <svg class="icon ms-n2">
                                        <use xlink:href="<?= asset('icons/sprite.svg') ?>#log-in"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </nav>
            <div class="container-fluid flex-grow-1">
                @if (! empty($metaTags->getTitle()))
                    <h1 class="mb-0">{{ $metaTags->getTitle() }}</h1>
                @endif

                @include('system::app/breadcrumbs')

                @yield('content')

                <div class="to-top to-top_hidden">
                    <button class="btn btn__top">
                        <svg class="icon-40">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#top"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="page_layout container">
        @stack('footer_content')
    </div>
    <div class="overlay"></div>
    <div class="page_layout justify-content-end d-flex w-100">
        <div></div>
        <div class="content-container">
            <div class="content-container-padding footer-padding">
                <div class="d-flex justify-content-between align-items-center border-top p-4 page-footer">
                    <div class="ps-1">
                        <a href="https://twitter.com/johncms" title="Twitter" target="_blank" rel="nofollow" class="me-3 text-muted text-decoration-none">
                            <svg class="icon">
                                <use xlink:href="<?= asset('icons/sprite.svg') ?>#twitter"/>
                            </svg>
                        </a>
                        <a href="https://t.me/johncms_official" title="Telegram" target="_blank" rel="nofollow" class="me-3 text-muted text-decoration-none">
                            <svg class="icon">
                                <use xlink:href="<?= asset('icons/sprite.svg') ?>#telegram"/>
                            </svg>
                        </a>
                        <a href="https://www.youtube.com/channel/UCIzwmZMHJgnBPEicpU9Itsw" title="YouTube" target="_blank" rel="nofollow" class="me-2 text-muted text-decoration-none">
                            <svg class="icon">
                                <use xlink:href="<?= asset('icons/sprite.svg') ?>#youtube2"/>
                            </svg>
                        </a>
                    </div>
                    <div class="flex-shrink-1 d-flex position-relative">
                        @if (! empty($analytics))
                            <!-- Counters -->
                            <div class="me-2 d-flex">
                                @foreach ($analytics as $counter)
                                    <div><?= $counter ?></div>
                                @endforeach
                            </div>
                        @endif
                        <div>
                            &copy; <?= date('Y') ?>, <a href="https://johncms.com" target="_blank" rel="nofollow" class="text-info">JohnCMS</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade ajax_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content"></div>
    </div>
</div>

@stack('scripts')
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
@if ($locale !== 'en')
    @php
        try {
    @endphp
    <script src="{{ asset('js/flatpickr/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/flatpickr/lang/' . $locale . '.js') }}"></script>
    <script>
        flatpickr('.flatpickr', {
            dateFormat: 'd.m.Y',
            locale: "{{ $locale }}"
        });
        flatpickr('.flatpickr_time', {
            dateFormat: 'd.m.Y H:i',
            enableTime: true,
            locale: "{{ $locale }}"
        });
    </script>
    @php
        } catch (Throwable) {}
    @endphp
@endif
</body>
</html>
