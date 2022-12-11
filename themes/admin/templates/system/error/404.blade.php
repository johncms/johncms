<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\NavChain;

/**
 * @var Mobicms\Render\Template\Template $this
 * @var string $message
 * @var string $title
 */

$nav_chain = di(NavChain::class);
$nav_chain->showHomePage(false);
?>
@extends('system::layout/default')

@section('content')
    <div class="row">
        <div class="col-md-6 m-auto">
            <div class="m-auto" style="max-width: 350px;">
                <img src="{{ asset('icons/404-error.svg') }}" alt="" class="img-fluid">
            </div>
            <div class="text-center">
                <div class="fw-bold h1">{{ $title }}</div>
                <p class="h2">{{ $message }}</p>
            </div>
        </div>
    </div>
@endsection
