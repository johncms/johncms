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
        <a href="{{ route('content.admin.type.create') }}" class="btn btn-primary">Create Type</a>
    </div>

    <div>
        <table class="table responsive-table">
            <thead>
            <tr>
                <th scope="col" style="width: 58px;" class="border-end-0"></th>
                <th scope="col" class="border-start-0" style="max-width: 1px;">#</th>
                <th scope="col">{{ __('Name') }}</th>
                <th scope="col">{{ __('Code') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data['contentTypes'] as $contentType)
                <tr>
                    <th scope="row" style="width: 40px;" class="border-end-0">
                        <div class="dropdown">
                            <button class="btn btn-sm" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <svg class="menu-icon">
                                    <use xlink:href="{{ asset('icons/sprite.svg') }}#menu"/>
                                </svg>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ $contentType['editUrl'] }}">{{ __('Edit') }}</a>
                                <a class="dropdown-item"
                                   data-url="{{ $contentType['deleteUrl'] }}"
                                   data-bs-toggle="modal"
                                   data-bs-target=".ajax_modal"
                                >{{ __('Delete') }}</a>
                            </div>
                        </div>
                    </th>
                    <th scope="row" class="border-start-0">
                        <a href="{{ $contentType['url'] }}">{{ $contentType['id'] }}</a>
                    </th>
                    <td data-title="{{ __('Name') }}">
                        <a href="{{ $contentType['url'] }}">{{ $contentType['name'] }}</a>
                    </td>
                    <td data-title="{{ __('Code') }}">
                        <a href="{{ $contentType['url'] }}">{{ $contentType['code'] }}</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center fw-bold">{{ __('The list is empty') }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
