@extends('system::layout/default')
@section('content')
    <div class="alert alert-success"><?= __('Thanks! Your e-mail has been successfully confirmed.') ?></div>
    <div class="mt-2">
        <a href="/" class="btn btn-primary"><?= d__('system', 'Go to the homepage') ?></a>
    </div>
@endsection
