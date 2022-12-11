<?php

/**
 * @var string $label
 * @var string $value
 * @var string $errors
 * @var string $locale
 * @var string $id
 * @var string $name
 * @var string $csrf_token
 */
if (isset($load_scripts) && $load_scripts !== false) {
    if ($locale !== 'en') {
        try {
            echo '<script src="' . asset('ckeditor5/translations/' . $locale . '.js', true) . '"></script>';
        } catch (Exception $exception) {
        }
    }
    ?>
    <script src="<?= asset('ckeditor5/ckeditor.js', true) ?>"></script>
    <?php
}
?>

<div class="vue_app">
    <ckeditor-input-component
        :class="'p-no-margin'"
        upload_url="<?= route('news.admin.sections.loadFile') ?>"
        name="<?= $name ?>"
        language="<?= $locale ?>"
        errors="<?= $errors ?>"
        id="<?= $id ?>"
        label="<?= $label ?>"
        value="<?= $value ?>"
        csrf_token="<?= $csrf_token ?>"
    ></ckeditor-input-component>
</div>
