<?php
/**
 * @var Johncms\Forms\Inputs\Select $field
 */
?>
<div class="mb-2">
    <label for="gender" class="form-label"><?= $field->label ?></label>
    <select id="<?= $field->id ?>" name="<?= $field->name ?>" class="form-select <?= (isset($errors[$field->name]) ? 'is-invalid' : '') ?>">
        <?php foreach ($field->options as $option): ?>
            <option value="{{ $option['value'] }}" <?= $option['value'] === $field->value ? 'selected' : '' ?>>{{ $option['name'] }}</option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors[$field->name])): ?>
        <div class="invalid-feedback"><?= implode('<br>', $errors[$field->name]) ?></div>
    <?php endif ?>
    <?php if ($field->helpText): ?>
        <div class="text-muted fz-small"><?= $field->helpText ?></div>
    <?php endif; ?>
</div>
