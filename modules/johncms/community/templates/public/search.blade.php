@extends('system::layout/default')
@section('content')
    @if ($errors)
        <div class="row">
            <div class="col-md-6">
                @include('system::app/alert',
                    [
                        'alert_type' => 'alert-danger',
                        'alert'      => $errors['search'],
                    ]
                )
            </div>
        </div>
    @endif
    <div class="row">
        <div class="">
            <form action="{{ route('community.search') }}" method="get" class="mb-3">
                <label for="search" class="form-label fw-bold">{{ __('Look for the User') }}:</label>
                <div class="input-group">
                    <input type="text" name="search" id="search"
                           class="form-control {{ isset($errors['search']) ? 'is-invalid':'' }}"
                           placeholder="{{ __('Username') }}"
                           value="{{ $search ?? null }}" required>
                    <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
                </div>
                <div
                    class="text-muted my-2 small">{{ __('The search is performed by Nickname and are case-insensitive.') }}</div>
            </form>
        </div>
    </div>


    <!-- List of Users -->
    @if($users && $users->total() > 0)
        @foreach ($users as $user)
            @include('johncms/community::public/user', ['user' => $user])
        @endforeach
        <div>
            <div>{{ __('Total') }}: {{ $users->total() }}</div>
            <!-- Page switching -->

            <div class="mt-1">{{ $users->render() }}</div>

        </div>
    @else
        <div class="alert alert-info">{{ __('List is empty') }}</div>
    @endif

    <div class="mt-2">
        <a href="{{ route('community.index') }}"><?= __('Back') ?></a>
    </div>
@endsection
