<?php
/**
 * @var Mobicms\Render\Template\Template $this
 * @var Johncms\Forms\Inputs\InputFile $field
 */

?>
<div class="mb-2">
    <label for="<?= $field->id ?>" class="form-label"><?= $field->label ?></label>

    <?php if (! empty($field->currentFile)): ?>
        <div class="mb-3">
            <div class="small"><?= __('Current File:') ?></div>
            <?php if ($field->currentFile['isImage']): ?>
                <div>
                    <a href="<?= $field->currentFile['url'] ?>" target="_blank">
                        <img
                            class="img-thumbnail"
                            src="<?= $field->currentFile['url'] ?>"
                            alt="<?= $this->e($field->currentFile['name']) ?>"
                            style="max-width: 150px; max-height: 150px;"
                        >
                    </a>
                </div>
            <?php endif; ?>
            <div>
                <a href="<?= $field->currentFile['url'] ?>" target="_blank"><?= e($field->currentFile['name']) ?></a>
            </div>
            <div class="form-check">
                <input
                    type="checkbox"
                    name="<?= $field->currentFile['delInputName'] ?? 'delete_' . $field->name ?>"
                    value="1"
                    class="form-check-input"
                    id="<?= $field->currentFile['delInputName'] ?? 'delete_' . $field->name ?>">
                <label class="form-check-label" for="<?= $field->currentFile['delInputName'] ?? 'delete_' . $field->name ?>"><?= __('Delete') ?></label>
            </div>
        </div>
    <?php endif; ?>
    <input type="<?= $field->type ?>"
           class="form-control <?= (isset($errors[$field->name]) ? 'is-invalid' : '') ?>"
           name="<?= $field->name ?>"
           id="<?= $field->id ?>"
           value="<?= e($field->value ?? '') ?>"
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
