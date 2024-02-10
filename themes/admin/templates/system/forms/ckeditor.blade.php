<?php
/**
 * @var Johncms\Forms\Inputs\CKEditor $field
 */

if ($locale !== 'en') {
    try {
        echo '<script src="' . asset('ckeditor5/translations/' . $locale . '.js', true) . '"></script>';
    } catch (Exception $exception) {
    }
}
?>
<script src="<?= asset('ckeditor5/ckeditor.js', true) ?>"></script>
<div class="vue_app">
    <ckeditor-input-component
            upload_url="<?= $field->uploadUrl ?>"
            name="{{ $field->name }}"
            language="<?= $locale ?>"
            errors="{!! implode('<br>', $errors[$field->name] ?? []) !!}"
            id="{{$field->id}}"
            label="{{$field->label}}"
            value="{{$field->value}}"
            csrf_token="{{$csrf_token}}"
            files-input-name="{{ $field->filesInputName }}"
    ></ckeditor-input-component>
</div>
