<div class="modal-header align-items-center">
    <h4 class="modal-title">{{ __('Select language') }}</h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <form action="{{ route('language.index') }}" method="post" name="select_language">
        {!! $csrf_input !!}
        @foreach($config['lng_list'] as $key => $val)
            <div class="form-check custom-radio mb-2">
                <input
                    type="radio"
                    id="lang_{{ $key }}"
                    name="setLng"
                    class="form-check-input"
                    value="{{ $key }}"
                    {!! ($key == $locale ? ' checked="checked"' : '') !!}
                >
                <label class="form-check-label" for="lang_{{ $key }}">
                    <img class="icon icon-flag" src="{{ asset('images/flags/' . strtolower($key) . '.svg') }}" alt=".">
                    {{ $val['name'] }}
                </label>
            </div>
        @endforeach
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary select_language">{{ __('Apply')}}</button>
</div>
