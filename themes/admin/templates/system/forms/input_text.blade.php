<?php
/**
 * @var Johncms\Forms\Inputs\InputText $field
 */
?>
<div class="mb-2">
    <label for="name" class="form-label"><?= $field->label ?></label>
    <input type="<?= $field->type ?>"
           class="form-control <?= (isset($errors[$field->name]) ? 'is-invalid' : '') ?>"
           name="<?= $field->name ?>"
           id="<?= $field->id ?>"
           value="{{$field->value ?? ''}}"
           placeholder="<?= $field->placeholder ?>"
        <?= $field->readOnly ? 'readonly' : '' ?>
    >
    <?php if (isset($errors[$field->name])): ?>
        <div class="invalid-feedback"><?= implode('<br>', $errors[$field->name]) ?></div>
    <?php endif ?>
    <?php if ($field->helpText): ?>
        <div class="text-muted fz-small"><?= $field->helpText ?></div>
    <?php endif; ?>
</div>
