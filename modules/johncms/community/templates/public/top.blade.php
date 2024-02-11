@extends('system::layout/default')
@section('content')

    <div class="mb-3 btn-group" role="group">
        <a href="{{ route('community.top', ['type' => 'forum'])}}"
           class="btn btn-outline-secondary mt-1 {{ ($activeTab === 'forum' ? 'active' : '') }}">{{ __('Forum') }}</a>
        <a href="{{ route('community.top', ['type' => 'guest'])}}"
           class="btn btn-outline-secondary mt-1 {{ ($activeTab === 'guest' ? 'active' : '') }}">{{ __('Guestbook') }}</a>
        <a href="{{ route('community.top', ['type' => 'comm'])}}"
           class="btn btn-outline-secondary mt-1 {{ ($activeTab === 'comm' ? 'active' : '') }}">{{ __('Comments') }}</a>
        @if(config('karma'))
            <a href="{{ route('community.top', ['type' => 'karma'])}}"
               class="btn btn-outline-secondary mt-1 {{ ($activeTab === 'comm' ? 'active' : '') }}">{{ __('Karma') }}</a>
        @endif
    </div>

    @if($users->total() > 0)
        @foreach ($users as $user)
            @include('johncms/community::public/user', ['user' => $user])
        @endforeach
    @else
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-info"><?= __('List is empty') ?></div>
            </div>
        </div>
    @endif

    @if($users->total() > 0)
        <div class="mt-2">
            <div>{{ __('Total') }}: {{ $users->total() }}</div>
            <!-- Page switching -->
            <div class="mt-1">{{ $users->render() }}</div>
        </div>
    @endif

    <div class="mt-2">
        <a href="{{ route('community.search') }}"><?= __('User Search') ?></a><br>
        <a href="{{ route('community.index') }}"><?= __('Back') ?></a>
    </div>
@endsection
