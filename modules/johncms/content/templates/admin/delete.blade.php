<form action="{{ $data['actionUrl'] }}" method="post">
    <input type="hidden" name="csrf_token" value="{{ $csrf_token }}">
    <div class="modal-header align-items-center">
        <h4 class="modal-title">
            {{ __('Confirm the Action') }}
        </h4>
        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
            <span class="icon">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <div><b>{{ $data['elementName'] }}</b></div>
        {{ __('Do you really want to delete?') }}
    </div>
    <div class="modal-footer">
        <button type="submit" name="delete" value="1" class="btn btn-danger">{{ __('Delete') }}</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    </div>
</form>
