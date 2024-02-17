<?php
/**
 * @var Johncms\Forms\Inputs\InputDateTime $field
 */

?>
<div class="mb-2">
    <label for="name" class="form-label">{{ $field->label }}</label>
    <input type="text"
           class="form-control {{ (isset($errors[$field->name]) ? 'is-invalid' : '') }} {{ $field->showTime ? 'flatpickr_time' : 'flatpickr'  }}"
           name="{{ $field->name }}"
           id="{{ $field->id }}"
           value="{{$field->value ?? ''}}"
           placeholder="{{ $field->placeholder }}"
        {{ $field->readOnly ? 'readonly' : '' }}
    >
    @if (isset($errors[$field->name]))
        <div class="invalid-feedback">{{ implode('<br>', $errors[$field->name]) }}</div>
    @endif
    @if ($field->helpText)
        <div class="text-muted fz-small">{{ $field->helpText }}</div>
    @endif
</div>
