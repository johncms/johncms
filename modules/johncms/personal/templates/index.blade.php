@extends('system::layout/default')
@section('content')
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4">
        <div class="col mb-2">
            <a href="<?= route('personal.profile') ?>" class="card text-center">
                <div class="card-body">
                    <div class="icon_with_badge d-inline-block">
                        <svg class="icon-40">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#user"/>
                        </svg>
                    </div>
                    <div class="mt-2 tile_name"><?= __('My profile') ?></div>
                </div>
            </a>
        </div>
        <div class="col mb-2">
            <a href="<?= route('personal.settings') ?>" class="card text-center">
                <div class="card-body">
                    <div class="icon_with_badge d-inline-block">
                        <svg class="icon-40">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#settings"/>
                        </svg>
                    </div>
                    <div class="mt-2 tile_name"><?= __('Settings') ?></div>
                </div>
            </a>
        </div>
        <div class="col mb-2">
            <a href="" class="card text-center">
                <div class="card-body">
                    <div class="icon_with_badge d-inline-block">
                        <svg class="icon-40">
                            <use xlink:href="<?= asset('icons/sprite.svg') ?>#messages"/>
                        </svg>
                    </div>
                    <div class="mt-2 tile_name"><?= __('Messages') ?></div>
                </div>
            </a>
        </div>
    </div>
@endsection

