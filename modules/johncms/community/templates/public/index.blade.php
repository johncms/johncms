@extends('system::layout/default')
@section('content')
    <div class="row">
        <div class="">
            <form action="{{ route('community.search') }}" method="get" class="mb-3">
                <label for="search" class="form-label fw-bold">{{ __('Look for the User') }}:</label>
                <div class="input-group">
                    <input type="text" name="search" id="search" class="form-control" placeholder="{{ __('Username') }}"
                           value="{{ $search ?? null }}">
                    <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                </div>
                <div
                    class="text-muted my-2 small">{{ __('The search is performed by Nickname and are case-insensitive.') }}</div>
            </form>
            <div class="list-group">
                <a href="{{ route('community.users') }}"
                   class="list-group-item d-flex justify-content-between align-items-center">
                    {{ __('Users') }}
                    <span class="badge bg-secondary rounded-pill">{{ $userCount }}</span>
                </a>
                <a href="{{ route('community.administration') }}"
                   class="list-group-item d-flex justify-content-between align-items-center">
                    {{ __('Administration') }}
                    <span class="badge bg-secondary rounded-pill">{{ $adminCount }}</span>
                </a>
                @if($birthDaysCount > 0)
                    <a href="{{ route('community.birthdays') }}"
                       class="list-group-item d-flex justify-content-between align-items-center">
                        {{ __('Birthdays') }}
                        <span class="badge bg-secondary rounded-pill">{{ $birthDaysCount }}</span>
                    </a>
                @endif
                <a href="{{ route('community.top') }}"
                   class="list-group-item d-flex justify-content-between align-items-center">
                    {{ __('Top Activity') }}
                </a>
            </div>
        </div>
    </div>
@endsection
