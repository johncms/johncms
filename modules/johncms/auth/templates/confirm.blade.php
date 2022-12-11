@extends('system::layout/default')
@section('content')
    @if($confirm === 'email')
        @include('system::app/alert', [
            'alert_type' => 'alert-info',
            'alert'      => __('You didn\'t confirm your email address. Please check your email and follow the instructions in it.'),
        ])
    @else
        @include('system::app/alert', [
            'alert_type' => 'alert-info',
            'alert'      => __('Sorry, but your request for registration is not considered yet. Please, be patient.'),
        ])
    @endif
@endsection
