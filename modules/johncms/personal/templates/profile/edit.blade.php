@extends('system::layout/default')
@section('content')
    <form method="post" class="mb-3" action="<?= $data['storeUrl'] ?>">
        @includeWhen(! empty($data['success']), 'system::app/alert', ['alert_type' => 'alert-success', 'alert' => $data['success']])
        @includeWhen(! empty($data['errors']), 'system::app/alert', ['alert_type' => 'alert-danger', 'alert' => $data['errors']])
        @include(
            'system::forms/simple_form',
            [
                'fields' => $data['formFields'],
                'errors' => $data['validationErrors'],
            ]
        )
        <button type="submit" name="submit" class="btn btn-primary"><?= __('Save') ?></button>
        <a href="{{ $data['backButton'] }}" class="btn btn-outline-secondary"><?= __('Back') ?></a>
    </form>
@endsection


