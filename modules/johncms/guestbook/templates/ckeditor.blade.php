<?php
/**
 * @var string $label
 * @var string $value
 * @var string $errors
 * @var string $locale
 * @var string $csrf_token
 * @var string $uploadUrl
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
        upload_url="<?= $uploadUrl ?>"
        name="message"
        language="<?= $locale ?>"
        errors="<?= $errors ?>"
        id="message2"
        label="<?= $label ?>"
        value="<?= $value ?>"
        csrf_token="<?= $csrf_token ?>"
    ></ckeditor-input-component>
</div>
