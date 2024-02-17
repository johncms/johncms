<input type="hidden" name="csrf_token" value="{{$csrf_token}}">

@if($fields)
    @foreach($fields as $field)
        @switch($field->type)
            @case('text')
            @case('password')
                @include('system::forms/input_text', ['field' => $field, 'errors' => $errors])
                @break

            @case('date')
                @include('system::forms/input_date', ['field' => $field, 'errors' => $errors])
                @break

            @case('hidden')
                @include('system::forms/input_hidden', ['field' => $field, 'errors' => $errors])
                @break

            @case('select')
                @include('system::forms/select', ['field' => $field, 'errors' => $errors])
                @break

            @case('textarea')
                @include('system::forms/textarea', ['field' => $field, 'errors' => $errors])
                @break

            @case('ckeditor')
                @include('system::forms/ckeditor', ['field' => $field, 'errors' => $errors])
                @break

            @case('captcha')
                @include('system::forms/captcha', ['field' => $field, 'errors' => $errors])
                @break

            @case('checkbox')
                @include('system::forms/checkbox', ['field' => $field, 'errors' => $errors])
                @break

            @case('file')
                @include('system::forms/input_file', ['field' => $field, 'errors' => $errors])
                @break
        @endswitch
    @endforeach
@endif

