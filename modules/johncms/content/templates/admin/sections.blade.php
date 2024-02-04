@extends('system::layout/default')
@section('content')
    @if($data['message'])
        <div>
            @include('system::app/alert',
               [
                   'alert_type' => 'alert-success',
                   'alert'      => e($data['message']),
               ])
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ $data['createSectionUrl'] }}" class="btn btn-primary">{{ __('Create Section') }}</a>
        <a href="{{ $data['createElementUrl'] }}" class="btn btn-primary">{{ __('Create Element') }}</a>
    </div>

    <div>
        <table class="table responsive-table">
            <thead>
            <tr>
                <th scope="col" style="width: 58px;" class="border-end-0"></th>
                <th scope="col" class="border-start-0" style="max-width: 1px;">#</th>
                <th scope="col"><?= __('Name') ?></th>
                <th scope="col"><?= __('Code') ?></th>
                <th scope="col"><?= __('Type') ?></th>
            </tr>
            </thead>
            <tbody>
            @foreach($data['sections'] as $section)
                <tr>
                    <th scope="row" style="width: 40px;" class="border-end-0">
                        <div class="dropdown">
                            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg class="menu-icon">
                                    <use xlink:href="{{ asset('icons/sprite.svg') }}#menu"/>
                                </svg>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ $section['editUrl'] }}"><?= __('Edit') ?></a>
                                <a class="dropdown-item"
                                   data-url="{{ $section['deleteUrl'] }}"
                                   data-bs-toggle="modal"
                                   data-bs-target=".ajax_modal"
                                ><?= __('Delete') ?></a>
                            </div>
                        </div>
                    </th>
                    <th scope="row" class="border-start-0">
                        <a href="{{ $section['url'] }}">{{ $section['id'] }}</a>
                    </th>
                    <td data-title="<?= __('Name') ?>">
                        <a href="{{ $section['url'] }}">{{ $section['name'] }}</a>
                    </td>
                    <td data-title="<?= __('Code') ?>">
                        <a href="{{ $section['url'] }}">{{ $section['code'] }}</a>
                    </td>
                    <td data-title="<?= __('Type') ?>">{{ __('Section')  }}</td>
                </tr>
            @endforeach

            @foreach($data['elements']['data'] as $element)
                <tr>
                    <th scope="row" style="width: 40px;" class="border-end-0">
                        <div class="dropdown">
                            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg class="menu-icon">
                                    <use xlink:href="{{ asset('icons/sprite.svg') }}#menu"/>
                                </svg>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ $element['url'] }}"><?= __('Edit') ?></a>
                                <a class="dropdown-item"
                                   data-url="{{ $element['deleteUrl'] }}"
                                   data-bs-toggle="modal"
                                   data-bs-target=".ajax_modal"
                                ><?= __('Delete') ?></a>
                            </div>
                        </div>
                    </th>
                    <th scope="row" class="border-start-0">
                        <a href="{{ $element['url'] }}">{{ $element['id'] }}</a>
                    </th>
                    <td data-title="<?= __('Name') ?>">
                        <a href="{{ $element['url'] }}">{{ $element['name'] }}</a>
                    </td>
                    <td data-title="<?= __('Code') ?>">
                        <a href="{{ $element['url'] }}">{{ $element['code'] }}</a>
                    </td>
                    <td data-title="<?= __('Type') ?>">{{ __('Element')  }}</td>
                </tr>
            @endforeach

            @if(empty($data['sections']) && empty($data['elements']['data']))
                <tr>
                    <td colspan="7" class="text-center fw-bold"><?= __('The list is empty') ?></td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
@endsection
