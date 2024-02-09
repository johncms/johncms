<?php
/**
 * @var $breadcrumbs
 */

use Johncms\NavChain;

$nav_chain = $container->get(NavChain::class);
$breadcrumbs = $nav_chain->getAll();
$last_item = array_key_last($breadcrumbs);
?>
@if (! empty($breadcrumbs))
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb" vocab="https://schema.org/" typeof="BreadcrumbList">
            @foreach ($breadcrumbs as $key => $breadcrumb)
                <li class="breadcrumb-item <?= ! empty($breadcrumb['active']) ? 'active' : '' ?>" property="itemListElement" typeof="ListItem">
                    @if (! empty($breadcrumb['url']))
                        <a @if ($last_item !== $key) property="item" typeof="WebPage" @endif href="{{ $breadcrumb['url'] }}" title="{{ $breadcrumb['name'] }}">
                            <span @if($last_item !== $key)property="name"@endif>{{ $breadcrumb['name'] }}</span>
                        </a>
                        @if ($last_item === $key)
                            <meta property="name" content="{{ $breadcrumb['name'] }}">
                        @endif
                    @else
                        <span property="name">{{ $breadcrumb['name'] }}</span>
                    @endif
                    <meta property="position" content="<?= ($key + 1) ?>">
                </li>
            @endforeach
        </ol>
    </nav>
@endif

