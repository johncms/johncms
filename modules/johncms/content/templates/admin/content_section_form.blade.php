@extends('system::layout/default')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <form method="post" class="mb-3" action="{{ $storeUrl }}">
                <h4>{{ $formTitle }}</h4>

                @include('system::forms/simple_form', [
                    'fields' => $formFields,
                    'errors' => $validationErrors,
                ])

                <div class="pt-2">
                    <button class="btn btn-primary me-1" type="submit">{{ __('Save') }}</button>
                    <a href="{{ $listUrl }}" class="btn btn-secondary">{{ __('Back') }}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
